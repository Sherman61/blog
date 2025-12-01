# Agent Instructions

You are a coding agent working on a **blog web app**.

## Tech stack

- Backend: **PHP 8** (procedural or light MVC, no heavy framework unless explicitly requested)
- Database: **MySQL** (schema is defined in `command.sql`)
- Frontend: HTML5, CSS (optionally Tailwind), minimal vanilla JavaScript
- Web server: Apache with PHP‑FPM (assume standard LAMP style hosting)

All server–side code must be written in **PHP** using **PDO** with prepared statements. 
Do not use any other backend language.

## Database

- Use the schema in **`command.sql`** as the single source of truth.
- Main entities: `users`, `categories`, `posts`, `comments`, `post_likes`.
- Only interact with tables that exist in that script unless the human explicitly asks to extend the schema.

## App goals (MVP)

1. Public blog:
   - List published posts by category (Thoughts / Mental Health / Opinions).
   - View single post page (includes content, like count, comments).
2. Auth:
   - User signup and login using `users` table.
   - Passwords hashed with `password_hash()` and verified with `password_verify()`.
   - Use PHP sessions for tracking logged‑in user.
3. Likes:
   - Logged‑in users can like/unlike posts.
   - Store likes in `post_likes` (one row per user/post).
4. Comments:
   - Logged‑in users can create comments on posts.
   - Comments stored in `comments` table, support parent_id for threaded replies but simple flat display is acceptable unless asked otherwise.

## Coding guidelines

- Use **PDO** for all DB operations:
  - Create a single reusable DB connection file (e.g. `config/db.php`).
  - Always use prepared statements to avoid SQL injection.
- Structure:
  - Put public entry files in `/public` (e.g. `index.php`, `post.php`, `login.php`, `signup.php`).
  - Put shared includes in `/includes` (e.g. `header.php`, `footer.php`, `auth.php`).
- Error handling:
  - Fail safely, never dump full stack traces or credentials.
  - On DB errors, log details to a file and show a generic message.

## What to build next

Unless the human gives more specific tasks, assume the next priorities:

1. `config/db.php` – create PDO connection using env/config variables.
2. Basic layout:
   - `includes/header.php`, `includes/footer.php`
   - Navigation with sections: Thoughts · Reflections · Realizations, Mental Health, Opinions
   - Login / Sign Up buttons in the header.
3. Public pages:
   - `public/index.php` – homepage with sections and latest posts.
   - `public/post.php` – single post page with like button and comments.
4. Auth pages:
   - `public/signup.php`
   - `public/login.php`
   - `public/logout.php`
5. Simple CSS file for layout consistent with the mockup.

Always keep code clear and well‑commented so a human can understand and extend it.