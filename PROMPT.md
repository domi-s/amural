You are scaffolding **Amural** (amural.eu), a platform where European teachers share, improve and remix lessons in every European language. Credit and income flow back to everyone whose work survives in a lesson. Version control for lessons, like Git but for teachers, is the core of the product.

Work in the phases below, in order. Commit after each phase with a clear message. Stop and tell me if a decision here is unclear or conflicts with something you find. Don't guess on the data model.

**For now, phase 0 with a landing page: a centred title and a short description, nothing else.** Find C:\Users\ez_re\Downloads\foundry.philibert-master\foundry.philibert-master\fonts\webfonts and clone here for the display font (Amural). Find details in DESIGN.md. No logo for now.

## Stack (decided)

- Latest stable Laravel, PHP 8.3+, **PostgreSQL** (use JSONB where noted).
- **Livewire + Alpine** for pages. *(If I change this to Inertia + Vue, only Phase 4 changes.)*
- **Tiptap** as the lesson editor: one JavaScript island, no real-time collaboration (no Yjs, no websockets).
- **Filament** for the admin panel.
- **Pest** for tests.
- Laravel queues on the database driver. Scout with the database driver.
- Keep it simple: no extra packages unless they save real work. Ask before adding anything not listed here.

## Phase 0: project setup

1. Create the Laravel app with a starter kit for auth (Livewire variant).
2. Configure PostgreSQL. Add a `docker-compose.yml` with just Postgres for local development.
3. Set up Pest, Laravel Pint and Larastan (level 6+).
4. Write a `CLAUDE.md` at the repo root that summarises this prompt: the product, the stack, the version-control principles and the conventions below. Keep it short.
5. Locales: English, Spanish and Romanian to start (`lang/en`, `lang/es`, `lang/ro`). All UI strings go through `__()`.

## Phase 1: the version-control domain (most important; test-first)

### Principles (must hold everywhere)

1. **History is append-only.** `revisions` and `block_versions` are never updated or deleted. Undo means a new revision. Enforce this in the models (throw on update/delete) and cover it with tests.
2. **Blocks have stable IDs** (UUIDs) that survive edits, moves, forks and translations.
3. **Nothing merges into someone else's lesson without a person accepting it.**
4. **Credit follows surviving text**, not edit counts.

### Tables / models

- `lessons`: id (uuid), owner_id, language (ISO code), title, licence (default `CC-BY-SA-4.0`), head_revision_id (nullable), forked_from_revision_id (nullable), translated_from_revision_id (nullable), archived_at (nullable), timestamps.
- `lesson_maintainers`: lesson_id, user_id, role.
- `blocks`: id (uuid), type (enum: heading, paragraph, image, embed, exercise, list), derived_from (JSONB array of block ids, for splits and merges), created_at.
- `block_versions`: id (uuid), block_id, content (JSONB, Tiptap node JSON), content_hash, author_id (nullable), author_type (`user` | `ai`), previous_version_id (nullable), created_at. Immutable.
- `revisions`: id (uuid), lesson_id, parent_id (nullable), merge_parent_id (nullable), author_id, message, licence, block_version_ids (JSONB ordered array: the full snapshot), created_at. Immutable.
- `drafts`: id, user_id, lesson_id, base_revision_id, content (JSONB), updated_at. Mutable, private, not history.
- `releases`: id, lesson_id, revision_id, name, published_at.
- `suggestions`: id, target_lesson_id, source_revision_id, base_revision_id, author_id, author_type, status (open, accepted, partially_accepted, rejected), message, timestamps. Plus `suggestion_comments` (per suggestion, optionally per block id).
- `contributions`: lesson_id, release_id, user_id, weight (decimal), calculated_at.
- `topics` and `lesson_topic`: **placeholder only** (id, name, slug). The topic web will be designed later, so don't model hierarchy or curriculum codes yet.

### Domain services (plain PHP in `app/Domain/Versioning`, no framework calls, fully unit-tested)

- `BlockDiffer`: compares two revisions block by block and returns added, removed, moved and changed blocks.
- `WordDiffer`: word-level diff of the text inside a changed block.
- `ThreeWayMerger`: given base, ours and theirs, merges per block. Changed on one side only → automatic. Changed on both → reported as a conflict. Moves aren't conflicts.
- `AttributionCalculator`: for a revision, traces each surviving word back to the block version (and so the author) that introduced it, and returns weights per author. Moves add no authorship. Split or merged blocks follow `derived_from`. Non-text blocks get a fixed weight per type from a config file (`config/amural.php`), with placeholder values.

### Actions (`app/Actions`, each in a DB transaction)

`SaveRevision` (draft → revision; new block versions only for changed blocks; optimistic lock: if the head moved since the draft's base, return a merge result instead of overwriting), `PublishRelease`, `ForkLesson`, `OpenSuggestion`, `AcceptSuggestion` (all or selected block ids; creates a merge revision with two parents; accepted block versions keep their original authors), `RejectSuggestion`, `PullUpstream` (opens a suggestion from the original into a fork), `RevertRevision` (whole revision or a single block; creates a new revision).

Events: `RevisionCreated`, `ReleasePublished`, `SuggestionAccepted`. `ReleasePublished` dispatches a queued `RecalculateContributions` job.

### Tests (Pest) must cover at least

- Saving an unchanged block reuses its block version.
- A fork credits the original authors.
- Accepting part of a suggestion credits the suggester only for the accepted blocks.
- A three-way merge with non-overlapping edits merges cleanly; overlapping edits produce a conflict.
- Moving a block changes no attribution.
- Deleting text removes its author's weight.
- Revisions and block versions can't be updated or deleted.
- Revert creates a new revision and doesn't touch old ones.

Use factories and a seeder that builds a realistic example: a secondary maths lesson in English with two revisions, a Spanish translation, a fork by a second teacher, and an open suggestion.

## Phase 2: policies

Policies for lessons and suggestions: owner and maintainers can save, publish and accept; any verified user can fork and suggest; anyone can read published releases. Add a `verified_at` and `country` column to users (country is an ISO code; never store ID documents). Verification itself comes later. For now an admin sets it in Filament.

## Phase 3: Filament admin

Resources for users (set verified and country), lessons (archive), suggestions (read-only), and a simple `takedown_requests` table with a resource for handling them. Redaction can be a stub that's clearly marked TODO.

## Phase 4: minimal UI (Livewire)

Keep it plain; design comes later. Light theme: background `#F6F1E7`, text `#141414`, Geologica for text.

- Lesson page: shows the latest release, plus a link to history.
- Editor: Tiptap in a `wire:ignore` block, autosaving to the draft, with a "Save version" button and a message field.
- History: list of revisions and releases, compare any two (using `BlockDiffer` and `WordDiffer`), "who wrote this" per block.
- Fork button; "Suggest changes" flow; suggestion review screen with per-block accept or reject, and conflict display.

## Conventions

- UUID primary keys for domain models.
- Business logic lives in actions and domain services, not in controllers, Livewire components or models.
- No AI, translation, payments or ID-verification integrations yet. Leave interfaces (`AiSuggester`, `Translator`) with fake implementations only.
- At the end, give me a short summary of what was built, what's stubbed, and any decisions you had to make.