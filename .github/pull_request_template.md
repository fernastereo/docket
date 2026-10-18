<!-- Pull request template. The `mr-info` skill drafts this text; the HTML comments are guidance and are removed from the final description. -->

## What changed

<!-- What was wrong or missing and how it was solved. A few bullets with the main changes (classes, routes, migrations, workflows) and the tests added. Mention anything deliberately left out of scope. -->

## Notes for deployment

<!-- Write `none` only when there really is nothing. The real dev/prod .env files are NOT in the repo (they live on the droplets), so a new or changed variable needs a manual step on the server before the deploy: say so explicitly. -->

- **Migrations:** <!-- new or changed files in docket/database/migrations/ (central) or docket/database/migrations/tenant/ (per curaduría). Name each one and what it does, especially if it changes or deletes data. -->
- **Env variables:** <!-- names only, never values. Add them by hand to the server's env file (dev.env / prod.env) before deploying. -->
- **Scheduled tasks:** <!-- changes in docket/routes/console.php -->
- **Commands to run after deploy:** <!-- e.g. a one-off data fix or a first manual run. `migrate` and `tenants:migrate` run automatically on deploy, so they are not listed here. -->
