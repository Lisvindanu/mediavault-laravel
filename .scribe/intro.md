# Introduction

MediaVault is an offline-first media queue manager that stores metadata only. The backend does NOT handle file uploads - all media files are stored locally on mobile devices.

<aside>
    <strong>Base URL</strong>: <code>http://mediavault.project-n.site</code>
</aside>

    This documentation provides all the information you need to integrate with the MediaVault API.

    **Important Notes:**
    - Backend stores **metadata only** (no file uploads)
    - Media files are stored **locally on devices**
    - Uses **Laravel Sanctum** Bearer token authentication
    - Rate limits: 60 requests/minute (general), 10 requests/hour (sync)

    <aside>As you scroll, you'll see code examples in different programming languages in the dark area to the right.
    You can switch the language used with the tabs at the top right.</aside>

