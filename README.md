# WebIT Site Runtime

This is the **single, shared** application that every WebIT-hosted website runs.
It is identical for all sites — a site's identity, content and credentials are
not in this code. They are bound at **pairing time** with a one-time
registration code and delivered by the platform over signed HTTPS.

You deploy this once per host and pair it; you never need a different copy per
site.

## Deploy on cPanel / other PHP hosting

1. **Clone this repository** to your hosting account (cPanel → *Git Version
   Control* → Clone, or `git clone <runtime repo url>`).
2. Create a database (the setup wizard imports `schema.sql` for you if it's empty).
3. Point your domain's document root at `public/`.
4. In your WebIT portal, open **Deploy & Sync → Generate registration code**.
5. Visit `https://yourdomain/setup.php`, enter the code + your database details,
   and click **Pair**. This binds the site to its reseller/website, pulls down
   its details, and writes credentials to `storage/site.local.php`
   (gitignored — never committed).
6. Run `php sync-client.php down` once to pull your content.

## Staying in sync

```
*/5 * * * * php /path/to/site/sync-client.php both >> /path/to/site/sync.log 2>&1
```

- **down:** pulls a read-only content snapshot from the platform.
- **up:** sends new orders / signups / form submissions (rows with
  `synced_up = 0`); `client_uid` makes this idempotent.

## Updating

Because every site shares this runtime, platform updates (bug fixes, new content
block types) reach you with a simple `git pull` — your content and credentials
in `storage/` are untouched.

## Notes

- Don't edit content here; edit it in the WebIT builder. Content tables are
  overwritten on each content sync.
- Platform API endpoint is configured in `config.sample.php` (`api.base`).
- To re-pair (e.g. after revoking credentials): delete `storage/site.local.php`
  and run setup again with a fresh code.
