# Video Reactions

Video Reactions is a Moodle activity where learners watch a video and register reactions at exact moments in the timeline. Reactions are synchronized to video time, aggregated to avoid visual noise, and available both as a learner-facing timeline and as teacher analytics.

The activity uses `local_video_bridge` for video sources and normalized playback adapters. Teachers choose which reactions are available, configure anti-spam limits, decide whether dense moments may trigger subtle playback animations, enable group behavior, and optionally configure completion and gradebook participation rules.

## Teacher flow

Create a Video Reactions activity, choose any Video Bridge source that supports reliable tracking, select the available reactions, and configure cooldown and per-minute limits. The activity can use Moodle groups, a minimum reaction count for completion, and an optional participation grade based on a configurable target number of reactions.

The report summarizes total reactions, counts by type, the most reacted moments, the most active learners, and reaction density along the video timeline. Every reported moment is a direct seek target, so a teacher can open the video at the exact point where the class reacted.

## Learner flow

Learners watch the video using the selected Video Bridge provider and can react with the configured reaction set. Each reaction is tied to the current video time. The interface can show only the learner's own reactions, aggregated class reactions, or hide reactions entirely.

Nearby reactions are grouped into short timeline buckets, which keeps a large class from turning the player into confetti. Dense moments may display a small, restrained animation while playback crosses that section.

The activity maintains a short-lived server-side playback session in addition to Video Bridge progress tracking. Reactions are accepted only when the request is close to recently observed playback, which makes arbitrary one-off calls to the reaction endpoint substantially less useful.

## Reports

Reports are built from grouped SQL queries rather than per-user or per-reaction rendering queries. They provide reaction totals, per-type distribution, high-density moments, active learners, and timeline density, with group-aware filtering through Moodle's group mode.

## Data and lifecycle

Video Reactions implements Moodle Privacy API, Backup/Restore, Course reset, Events, capabilities, AJAX external functions, Mustache templates, AMD modules, completion rules and optional gradebook integration.
