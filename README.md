# Sanitized Clone (`local_sanitizedclone`)

**Release metadata: 1.0.0. Experimental implementation: NOT approved for production-derived data.**

An administrative inventory and authorization interface plus a CLI-only, checkpointed
sanitization engine for pre-isolated Moodle clones. Requires Moodle 4.5 LTS or 5.x;
no external AI service, shell interpolation, PHP renderer, or destructive HTTP endpoint.
The current coverage is deliberately conservative and **always fails final safety
approval unless all unknown sources are reviewed through future schema-aware policies**.
A successful PHP run does not imply a safe data set.

## What exists

- Admin area with profiles, preflight inventory, explicit CLI authorization, status,
  operation counts and audit records. Profiles support stage enablement;
  disabling a stage always blocks final approval; fine-grained field strategies
  and profile editing are not implemented.
- Fixed stage registry, optional third-party adapters implementing
  `local_sanitizedclone\local\sanitizer\sanitizer_interface` at
  `\{frankenstyle_component}\local_sanitizedclone\sanitizer`.
- Deterministic HMAC names, unique usernames/emails in `example.invalid` with
  unchanged numeric user IDs; accounts set to `nologin`.
- Explicit field-by-field text scrubbing for selected Moodle tables, rather than
  global `REPLACE()` on serialized or binary content.
- Removal of selected credentials, session data, access logs and private attachments
  with the Moodle File API. Identifiable config values with recognized key names
  are cleared, but this **does not prove every secret was removed**.
- Per-table keyset batches and transactional checkpoints, Moodle Lock API,
  fail-closed inventory, elementary relational integrity queries and JSON manifest.
- Separate CLI cleanup for audit/checkpoints after 30 days; operation manifests remain.

## Independent destination attestation

1. Provision a new, private destination with a separately named database and dataroot,
   different HTTPS `wwwroot` and different database credentials than production.
2. Arrange out-of-band infrastructure verification that the destination database and
   external file store are clones, never live production. A textual assertion is
   insufficient proof of actual isolation or privileged write boundaries.
3. Generate a **destination-only** strong random token in the destination's secret
   manager, expose it to the CLI process as `SANITIZEDCLONE_DESTINATION_TOKEN`
   (at least 32 bytes) and store only its SHA-256 digest in the attestation file.
4. Create an external, regular file such as `/opt/clone-policy/destination.json`,
   readable only by the CLI service account (chmod 0600); it must be outside
   Moodle `dirroot` and `dataroot`. Example, using fictional values:

```json
{
  "destination_id": "clone_hml_20261014",
  "destination_dbname": "moodle_sanitized_hml",
  "destination_wwwroot": "https://clone.example.invalid",
  "destination_dataroot": "/srv/moodledata-clone",
  "source_dbname": "moodle_prod",
  "source_wwwroot": "https://moodle.example.org",
  "source_dataroot": "/srv/moodledata-prod",
  "token_sha256": "<sha256-of-destination-only-token>"
}
```

5. Create a fixed-policy profile in the admin interface, inspect the inventory and
   confirm authorization with the target `destination_id`. The authorization lasts
   one hour; if underlying inventory changes before CLI starts, execution refuses.
6. From the **destination CLI only** (with secrets securely injected through your
   orchestration system), run:

```bash
php local/sanitizedclone/cli/run.php --operation=1 \
  --attestation=/opt/clone-policy/destination.json --confirm-isolated \
  --manifest=/opt/clone-policy/manifest-1.json
```

Exit codes: `0` fully approved; `3` completed with unreviewed/blocking sources;
`1` failure; `2` invalid CLI arguments. The technical manifest is written as a
new, mode-0600 file outside the web root and dataroot. No raw personal values or
secret values are intentionally logged. Check the admin operation history.

A crash or exception retains completed checkpoints; rerun the same authorized
operation with the same **destination key**, using exactly the same arguments.
An operation in a `blocked` state may be rerun, but blockers cannot be overridden
via an HTTP checkbox.

To remove temporary audit and checkpoint entries for a terminal-state operation
older than 30 days:

```bash
php local/sanitizedclone/cli/purge.php --operation=1 \
  --attestation=/opt/clone-policy/destination.json \
  --confirm-isolated --confirm-purge
```

The operation's JSON report is retained. This cleanup does **not** delete
user/course data, repository files, external backups or production data.

## Privacy and correctness boundaries

The plugin does not modify Moodle numeric primary or foreign keys, course sections,
enrolments or grades. Only selected, known free-text columns are rewritten.
Message attachments, arbitrary HTML, multimedia and HTML-in-JSON require explicit
format-aware sanitizers. User information may still occur in grade feedback,
quiz/question/lesson content, calendar, rich text, activity metadata, backups,
plugin config, external LTI providers, logs outside Moodle DB, object storage and
third-party tables. Those sources are **unreviewed and blocking**, not silently
claimed to be clean. A plugin-specific adapter must explicitly declare its
coverage, including all its own tables and file areas, and provide tests.

Attachments are deleted only from explicitly registered File API areas;
nonmatching files remain and block validation. Deleting binary attachments
breaks embedded file links, intentionally. Moodle File API garbage collection may
retain underlying blobs temporarily, so storage reclamation must be assessed
separately. No filepool paths or `contenthash` are modified directly.

**Important:** The current inventory marks all nonempty uncovered tables and all
partially reviewed table schemas as blocked, plus external secrets and services.
Consequently a real, nonempty Moodle installation is expected to exit `3`. This
is a safety-first implementation scaffold, **not a proven complete sanitization
solution and not ready to release as fully functional 1.0.0**. It must not be
used as the sole control for exposing sensitive production data to developers.

## Testing and maintenance

Test cases in `tests/` exercise mapper stability, required isolation confirmation,
batch rollback/resume and fictional user IDs. The Behat scenario checks dashboard
behavior, not actual CLI sanitization. CI uses Moodle Plugin CI for 4.5/5.0, PHP
lint, codechecker, PHPUnit and Behat; no tag or release publishing is configured.
Only publish after running the external `moodle-plugin-validate/.chatgpt/run.py`
post-processor and `--check` on the exact plugin directory, reviewing rewrites,
and fixing all errors. Running PHP lint alone is not sufficient.
