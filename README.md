# Blog Prototype

This repo is the starting point for a PHP + MySQL blog app.

- `command.sql` – full database schema and seed data.
- `agents.md` – instructions for an AI coding agent (Codex / ChatGPT) describing the stack and next tasks.

To initialize the database:

```bash
mysql -u your_user -p < command.sql
```

Then create a PHP project (public, includes, config folders) following the guidelines in `agents.md`.