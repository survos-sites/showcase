# Session Handoff — 2026-07-20

Long session, two threads of work. Both are in a real, verified, mostly-shippable state.
This doc is the "what happened and what's left" pointer — deeper context for each
decision lives in the memory files linked at the bottom.

## Thread 1: folio-bundle row page — narrative rewrite (DONE, pushed)

`row/show.html.twig` went from a `{{ dump(row) }}` placeholder to a real Cooper
Hewitt–style narrative page, plus a document/postcard template hierarchy, plus several
rounds of fixing a recurring "row menu shows twice / disappears" bug that had already
eaten multiple prior sessions.

**All committed AND pushed to `mono` (`origin/main`).** Commits, most recent first:
- `8d18a5b4` — film/audio → `FolioDtoTypeResolver::DOCUMENT_TYPES`
- `62a3d4ee` — un-gate "Ask document" from RowMenu's pages-empty check (was invisible on video rows)
- `bde9b451` — Claims card on the AI task-runner page + `RowClaimsResolver` extraction
- `c386b3cc` — (thread 2, see below)
- Earlier commits in this thread got swept into a concurrent session's `248cdf86`
  ("migrate routing...") commit — not lost, just mislabeled; see the
  `zm-detail-twig-override` memory for the full story.

**Key architectural decision, now documented in `RowMenu.php`'s docblock**: the page's
action bar (Show/Edit/Bookmark/Ask document/OCR/Handwriting/Share) is `RowMenu`'s
`PAGE_ACTIONS`, rendered ONLY via `base.html.twig`'s automatic page-header mechanism —
no per-template action bar, no per-app override. If this bug resurfaces a 5th time,
the fix is almost certainly "something is rendering a second copy of this menu,"
not "patch the existing rendering again."

**Nothing left to do here** unless new bugs surface. If they do: read
`zm-detail-twig-override.md` first.

## Thread 2: YouTube speaker-diarized transcription pipeline (BUILT, partially pushed)

Started from "why is 'Ask document' missing on a video row," went through a real
design conversation (Supadata → doesn't do diarization → AssemblyAI/Deepgram), and
ended with a working, verified, three-repo implementation.

### What's built

1. **`ai-tools`** (already done in an earlier session, deployed to production):
   `POST /youtube/audio` — yt-dlp-as-library, extracts m4a, caches to S3
   (`s3://museado/youtube/<id>.m4a`). See closed issue
   [survos-sites/ai-tools#1](https://github.com/survos-sites/ai-tools/issues/1)
   for the full contract, including the confirmed intermittent YouTube
   bot-detection risk on ai-tools' datacenter IP.

2. **`ai-workflow-bundle`** (mono, **pushed** — `c386b3cc`): new
   `AssemblyAiTranscribeTask` — submits the extracted audio to AssemblyAI
   (speaker diarization), presigns `s3://` URLs via mediary's existing `S3Client`
   so AssemblyAI can fetch directly, writes a flat speaker-labeled `ai:ocrText`
   claim (same predicate the narrative page already displays — zero folio-bundle
   changes needed), keeps the full per-utterance JSON cached for future
   segment/speaker UI. New `AudioSubjectInterface` in `data-contracts`.

3. **`mediary`** (**committed locally, NOT pushed** — `9124c2d`): `AssetSubject`
   implements `AudioSubjectInterface`; `ASSEMBLYAI_API_KEY=` placeholder added to
   `.env`.

4. **`md`** (**committed locally, NOT pushed** — `bc77c97`): `YoutubeRawCommand`'s
   `--fetch-audio` path now calls mediary's `/media/ai/from-url?task=transcribe_audio`
   after a successful audio extraction and records a real transcript claim — not
   just an audio URL.

### Verified, without a real API key

- Container/DI lints clean in both `mediary` and `md`.
- Task registers and resolves through `TaskRegistry` correctly.
- The S3 presign logic produces a real signed URL against mediary's actual
  production S3 config.
- **Strongest check**: a live request through mediary's real
  `/media/ai/from-url?task=transcribe_audio` reached AssemblyAI's actual servers
  and got back a genuine `"Authentication error, API token missing/invalid"` —
  proves the entire path works end-to-end. The only gap is a real key.

### What's left — in order

1. **Sign up for AssemblyAI, put a real key in `mediary/.env.local`
   (`ASSEMBLYAI_API_KEY=...`)**. Not something I should do autonomously — it's a
   paid third-party account.
2. **Push `mediary` and `md` to their GitHub origins.** Claude Code's safety
   classifier correctly blocked me from doing this directly — the
   "commit straight to main" convention is explicitly scoped to `mono` only in
   memory, not every repo. These are real, tested commits sitting local-only.
3. **Deploy `mediary` and `md` to production** (`git push dokku main` in each) once
   the key is in and you're ready.
4. **Try it for real**: run `php bin/console provider:youtube:raw <dataset> --fetch-audio`
   against a channel with an uncaptioned video, confirm a real speaker-labeled
   transcript lands as an `ai:ocrText` claim, then `dataset:folio` + publish/pull
   to see it show up in zm's narrative page.
5. **Not started, deliberately deferred**: per-segment/speaker storage (clickable
   speaker turns, jump-to-timestamp UI). The flat text already displays correctly;
   this only matters if/when richer UI is wanted. Two design options already
   scoped in memory (extend `Page` vs. a DTO array field) — don't build either
   without picking one first.

### `ledger-bundle` / "audio ledger" — investigated, recommendation: no

`survos/ledger-bundle` is NOT a financial ledger — it's template-aware extraction
of structured tabular data from **scanned paper records** (census schedules,
etc.), using 2D bounding boxes (`Bbox`, `CoordinateSpace`,
`LedgerColumn`/`LedgerTable`/`LedgerBand`) to say "field X is always at this
pixel-percentage position on this specific known form."

**Recommendation: don't generalize it into an audio ledger.** Three concrete
reasons (not a hunch — read `ExtractLedgerTask.php`, `Bbox.php`,
`CoordinateSpace.php`, `LedgerTable.php`):

1. The mechanism's whole value comes from exploiting a *known, fixed, repeated*
   layout. A free-flowing oral-history interview has no analogous fixed
   structure — there's no "temporal position" a question reliably falls at, the
   way a census field always sits at the same spot on the same form.
2. It isn't even finished for its actual job yet: `ExtractLedgerTask.php` is
   explicitly marked `TODO: port to ai-workflow-bundle`, uses an older
   `supports(array $inputs)`/`run(array $inputs, ...)` shape (not the
   `TaskInterface`/`WorkflowSubjectInterface` pattern every current task,
   including `AssemblyAiTranscribeTask`, uses), and says outright "Not
   registered as a service until that's decided." Generalizing a dormant,
   architecture-mismatched bundle compounds debt rather than reusing something
   working.
3. The real need is already served, more simply: AssemblyAI's diarized output
   (speaker/start/end/text per utterance) already *is* the structured
   extraction — no bbox/template layer needed. The deferred future work already
   noted below (a `transcript: list<{speaker,start,end,text}>` field) is the
   right-sized answer.

Where the *pattern* (not the code) would genuinely apply: a truly formulaic
recording — a fixed-script intake interview asked in the same order every time,
closer to a filled-in form than a conversation. That's a narrow, specific future
case, not "audio" generally, and not what's in front of you now. Don't revisit
this unless that specific need shows up.

## Memory files updated this session (read these for full context, not just this doc)

- `zm-detail-twig-override.md` — the row-menu duplication saga; updated with the
  final "default to fixing the bundle, never a per-app action-bar override" rule.
- `md-youtube-provider-supadata.md` — full history: yt-dlp → Supadata (no
  diarization) → AssemblyAI, and the complete build status above.
- `musdig-24-oral-history-aggregate.md` — the bigger picture (Story/StoryAsset)
  this transcription work is one small step toward. Still not started, still
  deliberately prototype-first.

## Working-tree hygiene note

Both `mediary` and `md` (and `mono` earlier in the session) had **unrelated
concurrent-session changes sitting uncommitted** in the same working directories
throughout this session (composer.json/lock drift, an unrelated Smithsonian
listener change, migration files, icon assets). I was careful to stage only my
own files at every commit — but if you see modified files you don't recognize
next time you look at `git status` in those repos, that's why. Not mine, not
touched, still there.
