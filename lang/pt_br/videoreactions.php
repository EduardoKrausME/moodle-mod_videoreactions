<?php
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
 * videoreactions.php
 *
 * @package   mod_videoreactions
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['availablereactions'] = 'Reações disponíveis';
$string['availablereactions_help'] = 'Escolha quais tipos de reação os alunos poderão usar durante o vídeo.';
$string['classreactions'] = 'Reações da turma';
$string['completiondetail:reactions'] = 'Fazer pelo menos {$a} reações aceitas';
$string['completionreactions'] = 'Quantidade mínima de reações aceitas';
$string['cooldown'] = 'Intervalo entre reações';
$string['cooldown_help'] = 'Quantidade mínima de segundos entre duas reações aceitas do mesmo aluno.';
$string['count'] = 'Reações';
$string['density'] = 'Densidade na timeline';
$string['error:cooldown'] = 'Aguarde um pouco antes de reagir novamente.';
$string['error:invalidtime'] = 'O instante informado do vídeo é inválido.';
$string['error:playbacknotverified'] = 'Não foi possível validar a reação com a reprodução recente do vídeo.';
$string['error:ratelimit'] = 'O limite de reações deste minuto foi atingido.';
$string['error:reactiondisabled'] = 'Esta reação não está habilitada nesta atividade.';
$string['eventcoursemoduleviewed'] = 'Atividade Video Reactions visualizada';
$string['eventreactionadded'] = 'Reação ao vídeo adicionada';
$string['gradetarget'] = 'Reações para obter a nota máxima';
$string['gradetarget_help'] = 'Quando a nota estiver habilitada, esta quantidade de reações aceitas corresponde à nota máxima.';
$string['gradingnote'] = 'Defina a nota como Nenhuma para desabilitar a avaliação por participação.';
$string['hidereactions'] = 'Esconder reações';
$string['learner'] = 'Aluno';
$string['maxperminute'] = 'Máximo de reações por minuto';
$string['maxperminute_help'] = 'Limite validado no servidor por aluno. Use 0 para não limitar por minuto.';
$string['modulename'] = 'Video Reactions';
$string['modulenameplural'] = 'Video Reactions';
$string['moment'] = 'Momento';
$string['myreactions'] = 'Somente minhas reações';
$string['noreactions'] = 'Ainda não há reações.';
$string['nosourceplugins'] = 'Nenhuma fonte do Video Bridge com tracking confiável está disponível.';
$string['openmoment'] = 'Abrir o vídeo neste momento';
$string['participationheader'] = 'Participação';
$string['pluginname'] = 'Video Reactions';
$string['privacy:metadata'] = 'Video Reactions armazena reações dos alunos e dados temporários de validação da reprodução.';
$string['privacy:metadata:reaction'] = 'Armazena as reações feitas pelos alunos em instantes exatos do vídeo.';
$string['privacy:metadata:reaction:reaction'] = 'A reação escolhida.';
$string['privacy:metadata:reaction:timecreated'] = 'Quando a reação foi registrada.';
$string['privacy:metadata:reaction:userid'] = 'O aluno que realizou a reação.';
$string['privacy:metadata:reaction:videotime'] = 'A posição do vídeo associada à reação.';
$string['privacy:metadata:session'] = 'Armazena o estado temporário usado para validar a reprodução.';
$string['privacy:metadata:session:lastposition'] = 'A última posição do vídeo validada.';
$string['privacy:metadata:session:userid'] = 'O aluno cuja reprodução está sendo validada.';
$string['privacy:metadata:session:watchedmap'] = 'Mapa normalizado dos trechos observados recentemente.';
$string['react'] = 'Reagir';
$string['reactiondistribution'] = 'Reações por tipo';
$string['reactionrequired'] = 'Selecione pelo menos uma reação.';
$string['reactionsheader'] = 'Reações';
$string['report'] = 'Relatório de reações';
$string['resetreactions'] = 'Excluir reações e estado de reprodução dos alunos';
$string['resetreactionsstatus'] = 'Dados de usuários do Video Reactions excluídos';
$string['seconds'] = '{$a} segundos';
$string['showanimations'] = 'Mostrar animação discreta em momentos intensos';
$string['showanimations_help'] = 'Quando a reprodução passar por um trecho com muitas reações, mostra um pequeno indicador visual sem transformar o player em carnaval.';
$string['sourceheader'] = 'Fonte do vídeo';
$string['timeline'] = 'Timeline de reações';
$string['topmoments'] = 'Momentos mais reagidos';
$string['topstudents'] = 'Alunos que mais interagiram';
$string['totalreactions'] = 'Total de reações';
$string['unlimited'] = 'Sem limite';
$string['videoreactionsname'] = 'Nome da atividade';
$string['videosource'] = 'Fonte do vídeo';
$string['viewreport'] = 'Ver relatório de reações';
