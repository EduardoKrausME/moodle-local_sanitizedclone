<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Traduções brasileiras.
 *
 * @package local_sanitizedclone
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allstagesrequired'] = 'Todas as etapas de sanitização são obrigatórias.';
$string['alreadyrunning'] = 'Outra sanitização está em andamento.';
$string['analyzed'] = 'Fontes reconhecidas (cobertura parcial)';
$string['authorizecli'] = 'Autorizar execução por CLI';
$string['blockers'] = 'Fontes não revisadas ou bloqueadoras';
$string['cli'] = 'Comando CLI';
$string['clifailed'] = 'A sanitização falhou ({$a}); consulte o registro de auditoria sem dados sensíveis.';
$string['clihelp'] = 'Uso: php local/sanitizedclone/cli/run.php --operation=ID --attestation=/secure/destination.json --confirm-isolated [--manifest=/secure/manifest.json]';
$string['confirmationrequired'] = 'É necessária autorização explícita.';
$string['confirmtext'] = 'Autorizo a execução exclusivamente numa cópia isolada, nunca em produção.';
$string['count'] = 'Quantidade';
$string['created'] = 'Criado em';
$string['createprofile'] = 'Criar perfil com política fixa';
$string['destinationid'] = 'Identificador da instalação de destino';
$string['eventdescriptionoperation_authorised'] = 'A operação de sanitização {$a} foi autorizada.';
$string['eventdescriptionoperation_finished'] = 'A operação de sanitização {$a} foi finalizada.';
$string['eventdescriptionoperation_started'] = 'A operação de sanitização {$a} foi iniciada.';
$string['eventoperation_authorised'] = 'Operação de sanitização autorizada';
$string['eventoperation_finished'] = 'Operação de sanitização concluída';
$string['eventoperation_started'] = 'Operação de sanitização iniciada';
$string['expiredplan'] = 'A autorização expirou. Autorize um novo plano.';
$string['invalidattestation'] = 'A declaração de destino é inválida.';
$string['invaliddestination'] = 'Identificador de destino inválido.';
$string['invalidmanifestpath'] = 'O manifesto deve ser um novo arquivo fora da raiz web e do dataroot.';
$string['invalidprofilename'] = 'Informe um nome de perfil válido.';
$string['invalidstate'] = 'O estado da operação não permite execução.';
$string['manifestwritefailed'] = 'Não foi possível gravar o manifesto técnico.';
$string['nooperations'] = 'Nenhuma operação autorizada.';
$string['noprofiles'] = 'Nenhum perfil cadastrado.';
$string['operation'] = 'Operação';
$string['operations'] = 'Operações e histórico de execução';
$string['planchanged'] = 'O inventário mudou desde a autorização. Autorize novo plano.';
$string['planfingerprint'] = 'SHA-256 do plano:';
$string['pluginname'] = 'Clone anonimizado';
$string['preview'] = 'Prévia do inventário (somente leitura)';
$string['privacy:metadata'] = 'O plugin armazena perfis, contagens e estados das operações sem dados pessoais nas próprias tabelas. Os eventos do Moodle registram o administrador responsável.';
$string['profilename'] = 'Nome do perfil';
$string['profiles'] = 'Perfis de sanitização';
$string['progress'] = 'Checkpoints concluídos / total';
$string['purged'] = 'Registros temporários de auditoria e checkpoints da operação {$a} foram removidos.';
$string['purgenotallowed'] = 'A limpeza exige operação finalizada e mais de 30 dias desde a criação.';
$string['purgeusage'] = 'Uso: php local/sanitizedclone/cli/purge.php --operation=ID --attestation=/secure/destination.json --confirm-isolated --confirm-purge';
$string['reason'] = 'Motivo';
$string['safetywarning'] = 'Atenção: nunca execute na produção. A execução exige declaração do destino isolado e segredo independente. Fontes desconhecidas impedem aprovação.';
$string['sanitizedclone%3Aauthorize'] = 'Autorizar sanitização de cópia isolada';
$string['sanitizedclone%3Amanageprofiles'] = 'Gerenciar perfis de sanitização';
$string['sanitizedclone%3Aview'] = 'Visualizar painel de sanitização';
$string['source'] = 'Fonte de dados';
$string['stage'] = 'Sanitizador';
$string['stageassignments'] = 'Textos de tarefas';
$string['stagecredentials'] = 'Sessões e tokens de acesso';
$string['stagecustomfields'] = 'Campos personalizados';
$string['stagefiles'] = 'Anexos sensíveis';
$string['stageforums'] = 'Publicações de fórum';
$string['stagelogs'] = 'Logs de atividades';
$string['stagemessages'] = 'Mensagens';
$string['stageoptions'] = 'Etapas incluídas neste perfil (etapas desabilitadas impedem a aprovação)';
$string['stagesecrets'] = 'Segredos de configuração reconhecidos';
$string['stageusers'] = 'Perfis de usuários';
$string['status'] = 'Estado';
$string['unreviewed'] = 'Não analisados (impedem aprovação)';
$string['unsafeenvironment'] = 'Execução recusada: não foi possível atestar o isolamento do destino.';
