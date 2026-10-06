// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * reactions.js
 *
 * @package   mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Video Reactions player integration.
 *
 * @module mod_videoreactions/reactions
 */

define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    const HEARTBEAT_MS = 2500;
    const BUCKET_SECONDS = 5;

    const formatTime = (seconds) => {
        seconds = Math.max(0, Math.floor(Number(seconds) || 0));
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor(seconds / 60) % 60;
        const secs = seconds % 60;
        return hours > 0
            ? [hours, minutes, secs].map((value) => String(value).padStart(2, '0')).join(':')
            : [minutes, secs].map((value) => String(value).padStart(2, '0')).join(':');
    };

    const createPlayer = (root, config) => new Promise((resolve, reject) => {
        const module = config.player && config.player.adaptermodule;
        if (!module) {
            reject(new Error('Missing Video Bridge adapter module.'));
            return;
        }

        require([module], (provider) => {
            if (!provider || typeof provider.create !== 'function') {
                reject(new Error('Invalid Video Bridge adapter.'));
                return;
            }
            Promise.resolve(provider.create(root, config.player)).then(resolve).catch(reject);
        }, reject);
    });

    class Reactions {
        constructor(root) {
            this.root = root;
            this.playerRoot = root.querySelector('[data-region="player"]');
            this.timeline = root.querySelector('[data-region="timeline"]');
            this.density = root.querySelector('[data-region="density"]');
            this.animation = root.querySelector('[data-region="reaction-animation"]');
            this.modeSelect = root.querySelector('[data-action="display-mode"]');
            this.buttons = Array.from(root.querySelectorAll('[data-action="react"]'));
            this.config = this.readConfig();
            this.playing = false;
            this.lastHeartbeatAt = 0;
            this.heartbeatPromise = null;
            this.clusters = new Map();
            this.lastAnimationBucket = null;
            this.denseThreshold = Number.POSITIVE_INFINITY;
        }

        readConfig() {
            const element = this.root.querySelector('[data-region="config"]');
            if (!element) {
                return {};
            }
            try {
                return JSON.parse(element.textContent || '{}');
            } catch (error) {
                return {};
            }
        }

        initialise() {
            this.setReactionButtons(false);
            return createPlayer(this.playerRoot, this.config).then((player) => {
                this.player = player;
                this.bindPlayer();
                this.bindControls();

                const start = Number(this.config.starttime || 0);
                if (start > 0 && this.canSeek()) {
                    this.player.seek(start);
                }

                return this.loadTimeline();
            }).catch((error) => {
                Notification.exception(error);
            });
        }

        bindPlayer() {
            this.player.onPlay(() => {
                this.playing = true;
                this.setReactionButtons(Boolean(this.config.canreact));
                this.sendHeartbeat(true);
            });

            this.player.onPause(() => {
                this.playing = false;
                this.setReactionButtons(false);
            });

            this.player.onEnded(() => {
                this.playing = false;
                this.setReactionButtons(false);
            });

            this.player.onTimeUpdate((position) => {
                const now = Date.now();
                if (this.playing && now - this.lastHeartbeatAt >= HEARTBEAT_MS) {
                    this.sendHeartbeat(false);
                }
                this.maybeAnimate(Number(position || 0));
            });
        }

        bindControls() {
            this.buttons.forEach((button) => {
                button.addEventListener('click', () => this.react(button));
            });

            if (this.modeSelect) {
                this.modeSelect.addEventListener('change', () => this.loadTimeline());
            }
        }

        setReactionButtons(enabled) {
            this.buttons.forEach((button) => {
                button.disabled = !enabled;
            });
        }

        sendHeartbeat(force) {
            if (!this.player || (!this.playing && !force)) {
                return Promise.resolve(null);
            }

            const now = Date.now();
            if (!force && now - this.lastHeartbeatAt < HEARTBEAT_MS) {
                return this.heartbeatPromise || Promise.resolve(null);
            }

            if (this.heartbeatPromise) {
                return this.heartbeatPromise;
            }

            this.lastHeartbeatAt = now;
            this.heartbeatPromise = Ajax.call([{
                methodname: 'mod_videoreactions_track_watch',
                args: {
                    cmid: Number(this.config.cmid),
                    position: Number(this.player.getCurrentTime() || 0),
                    duration: Number(this.player.getDuration() || 0)
                }
            }])[0].catch((error) => {
                Notification.exception(error);
                return null;
            }).finally(() => {
                this.heartbeatPromise = null;
            });

            return this.heartbeatPromise;
        }

        react(button) {
            if (!this.player || !this.playing || !this.config.canreact) {
                return;
            }

            const reaction = button.dataset.reaction || '';
            const previousDisabled = this.buttons.map((item) => item.disabled);
            this.setReactionButtons(false);

            this.sendHeartbeat(true).then(() => {
                if (!this.playing) {
                    return null;
                }
                return Ajax.call([{
                    methodname: 'mod_videoreactions_save_reaction',
                    args: {
                        cmid: Number(this.config.cmid),
                        reaction: reaction,
                        videotime: Number(this.player.getCurrentTime() || 0),
                        duration: Number(this.player.getDuration() || 0)
                    }
                }])[0];
            }).then((response) => {
                if (response && response.success) {
                    return this.loadTimeline();
                }
                return null;
            }).catch((error) => {
                Notification.exception(error);
            }).finally(() => {
                this.buttons.forEach((item, index) => {
                    item.disabled = !this.playing || previousDisabled[index];
                });
            });
        }

        loadTimeline() {
            const mode = this.modeSelect ? this.modeSelect.value : 'mine';
            if (mode === 'hidden') {
                this.clusters.clear();
                this.timeline.replaceChildren();
                this.density.replaceChildren();
                this.denseThreshold = Number.POSITIVE_INFINITY;
                return Promise.resolve();
            }

            return Ajax.call([{
                methodname: 'mod_videoreactions_get_timeline',
                args: {
                    cmid: Number(this.config.cmid),
                    mode: mode
                }
            }])[0].then((response) => {
                this.buildClusters(Array.isArray(response.rows) ? response.rows : []);
                this.renderTimeline();
                this.renderDensity();
            }).catch((error) => {
                Notification.exception(error);
            });
        }

        buildClusters(rows) {
            this.clusters = new Map();
            rows.forEach((row) => {
                const bucket = Number(row.timebucket || 0);
                const count = Number(row.count || 0);
                if (!this.clusters.has(bucket)) {
                    this.clusters.set(bucket, {
                        bucket: bucket,
                        total: 0,
                        reactions: []
                    });
                }
                const cluster = this.clusters.get(bucket);
                cluster.total += count;
                cluster.reactions.push({
                    reaction: String(row.reaction || ''),
                    count: count
                });
            });

            let maximum = 0;
            this.clusters.forEach((cluster) => {
                maximum = Math.max(maximum, cluster.total);
                cluster.reactions.sort((a, b) => b.count - a.count);
            });
            this.denseThreshold = maximum >= 3 ? Math.max(3, Math.ceil(maximum * 0.6)) : Number.POSITIVE_INFINITY;
        }

        renderTimeline() {
            this.timeline.replaceChildren();
            const duration = Number(this.player.getDuration() || 0);
            if (duration <= 0) {
                return;
            }

            this.clusters.forEach((cluster) => {
                const marker = document.createElement('button');
                marker.type = 'button';
                marker.className = 'videoreactions-marker';
                marker.style.left = Math.min(100, Math.max(0, cluster.bucket / duration * 100)) + '%';
                marker.disabled = !this.canSeek();

                const primary = cluster.reactions[0] ? cluster.reactions[0].reaction : '•';
                const emoji = document.createElement('span');
                emoji.setAttribute('aria-hidden', 'true');
                emoji.textContent = primary;
                marker.appendChild(emoji);

                if (cluster.total > 1) {
                    const badge = document.createElement('span');
                    badge.className = 'videoreactions-marker-count';
                    badge.textContent = String(cluster.total);
                    marker.appendChild(badge);
                }

                const breakdown = cluster.reactions
                    .map((item) => item.reaction + '×' + item.count)
                    .join(' ');
                marker.title = formatTime(cluster.bucket) + ' — ' + breakdown;
                marker.setAttribute('aria-label', marker.title);

                if (this.canSeek()) {
                    marker.addEventListener('click', () => this.player.seek(cluster.bucket));
                }
                this.timeline.appendChild(marker);
            });
        }

        renderDensity() {
            this.density.replaceChildren();
            const clusters = Array.from(this.clusters.values()).sort((a, b) => a.bucket - b.bucket);
            if (!clusters.length) {
                return;
            }

            const maximum = Math.max(...clusters.map((cluster) => cluster.total), 1);
            clusters.forEach((cluster) => {
                const bar = document.createElement('div');
                bar.className = 'videoreactions-density-bar';
                bar.style.height = Math.max(8, Math.round(cluster.total / maximum * 100)) + '%';
                bar.title = formatTime(cluster.bucket) + ' — ' + cluster.total;
                this.density.appendChild(bar);
            });
        }

        maybeAnimate(position) {
            if (!this.config.showanimations || !this.playing || !this.animation || !Number.isFinite(this.denseThreshold)) {
                return;
            }

            const bucket = Math.floor(position / BUCKET_SECONDS) * BUCKET_SECONDS;
            const cluster = this.clusters.get(bucket);
            if (!cluster || cluster.total < this.denseThreshold || this.lastAnimationBucket === bucket) {
                return;
            }

            this.lastAnimationBucket = bucket;
            const primary = cluster.reactions[0] ? cluster.reactions[0].reaction : '';
            this.animation.textContent = primary + (cluster.total > 1 ? ' ' + cluster.total : '');
            this.animation.classList.remove('is-visible');
            void this.animation.offsetWidth;
            this.animation.classList.add('is-visible');
        }

        canSeek() {
            const capabilities = this.config.player && this.config.player.capabilities;
            return !capabilities || capabilities.seeking !== false;
        }
    }

    const init = () => {
        document.querySelectorAll('[data-region="videoreactions-root"]').forEach((root) => {
            (new Reactions(root)).initialise();
        });
    };

    return {init: init};
});
