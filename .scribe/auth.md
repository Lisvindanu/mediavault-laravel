# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can retrieve your token by calling <code>POST /api/register</code> or <code>POST /api/login</code>. Use the token in the Authorization header as <code>Bearer {token}</code>.
