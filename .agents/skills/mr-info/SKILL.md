---
name: mr-info
description: Draft the commit message and the pull request title and description for the current branch, following the Github Default PR template. Use when the user asks for a commit message, an PR/pull request title or description, or "the commit and PR info".
argument-hint: "[base branch: dev (default) | main]"
---


# Commit message and PR info

Draft (never execute) the commit message and the pull request title and
description for the work on the current branch.

**Do not** commit, push or create the PR. The developer does that.
All the drafted texts are **in English**, even if the conversation is in
another language.

## 1. Read the conventions
This will be defined soon.

## 2. Get the ClickUp ID from the branch
In this project we are not using ClickUp or any other management app, for now omit this section.

## 3. Collect the changes

Base branch: the argument passed to the skill, if any (`dev` by default, or `main`)

- **Commit message:** use the staged changes (`git diff --cached`). If nothing
  is staged, say so and use the last commit not yet in the base branch
  (`git log <base>..HEAD`). If there is no such commit either, use the
  working-tree changes (`git diff` and the untracked files), and give the
  exact `git add …` command the developer has to run before committing.
- **PR:** use everything the branch adds on top of the base
  (`git log <base>..HEAD` and `git diff <base>...HEAD`) plus the staged
  changes.
- Run `git status --short`. If there are unstaged or untracked files, list
  them and say they are **not** included (except in the working-tree case
  above, where they are the change itself).
- **Flag suspicious staged changes** before drafting: debug code, `dump()` /
  `dd()` / `console.log`, commented-out code, credentials or real values in
  `.env*` files, local-only tweaks (e.g. disabled authentication), and files
  unrelated to the ticket. Ask whether they should be unstaged.

Also use what was discussed in the conversation (e.g. agreed scope or things
that were deliberately left out). It explains the *why* better than the diff.

## 4. Fill in "Notes for deployment"

Check the branch diff against the base. Write `none` only when there really
is nothing:

- **Migrations:** new or changed files in `docket/database/migrations/`
  (central database) or `docket/database/migrations/tenant/` (each
  curaduría's database). Name each one and say what it does, especially if it
  changes or deletes data, or adds a `NOT NULL` column (it fails on a database
  that already has rows).
- **Env variables:** changes in `docket/.env.example` and `infra/env/*.env.example`
  (name the variables, never their values). The real `dev.env` / `prod.env` are
  **not in the repo** (they live on the droplets), so a new or changed variable
  needs a manual step on the server **before** the deploy: say so explicitly.
- **Scheduled tasks:** changes in `docket/routes/console.php`.
- **Commands to run after deploy:** e.g. a one-off data fix or a command that
  needs a first manual run. `migrate` and `tenants:migrate` run automatically
  on deploy (`.github/workflows/deploy.yml`), as does the image build, so they
  are not listed here.

## 5. Output

Give the texts in this order, each one in its own fenced code block, ready to
copy:

1. **Commit message:** free text, imperative form. One short
   summary line. Add a body (after a blank line) only if the change needs an
   explanation.
2. **PR title:** `Imperative description`, short.
3. **PR description:** the content of `.github/pull_request_template.md`
   without the HTML comments (`<!-- … -->`), with:
   - **What changed:** short, because nobody reads long PRs. A one- or
     two-sentence summary of what was missing and why, then **at most 5
     bullets**, one line each, on the main changes. Rules:
     - Say *what* and *why*, not *how*: no lists of classes, files or
       endpoints (the diff already shows them), no step-by-step flows.
     - Tests: one line at most ("Pest and Vitest cover X, Y"), never one
       bullet per test.
     - Docs updated: skip it, unless the docs are the whole change.
     - Mention a deliberate limitation or something left out of scope only if
       the reviewer could mistake it for a bug; one line.
     - Keep the details that a reviewer could not guess from the diff (a
       subtle bug found, a security trade-off), in one line each.
     - Target: under ~120 words in total. If it is longer, cut it.
   - **Notes for deployment:** from step 4, only the items that apply, one
     line each. Write `none` for the whole section when nothing applies,
     instead of listing every item with "none".

After the blocks, remind the developer of the PR options:
merge commit (no squash), "Delete source branch" checked, 1 approval from
another developer.
