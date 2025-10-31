# Leon Popup Gate project

This repository contains the source code and packaged ZIP releases for the **Leon Popup Gate (AFF Pairs)** WordPress plugin.

## Repository layout

- `leon-popup-gate-affpairs/` – Plugin source code.
- `leon-popup-gate-affpairs-v1.2.0.zip` – Packaged plugin release for version 1.2.0.
- `leon-popup-gate-affpairs-v1.2.1.zip` – Packaged plugin release for version 1.2.1.
- `leon-popup-gate-affpairs-v1.2.2.zip` – Packaged plugin release for version 1.2.2 (current).

## Downloading the ZIP

If you are working inside the provided container environment, the shell prompt will look similar to `root@7115a1c9075a:/workspace/leon#`. That prompt simply shows the user, container ID, and current directory. To download a ZIP, issue the command after the `#` symbol, for example:

```bash
root@7115a1c9075a:/workspace/leon# cp leon-popup-gate-affpairs-v1.2.2.zip /workspace/
```

You can then retrieve `/workspace/leon-popup-gate-affpairs-v1.2.2.zip` using your IDE or file browser.

## Development notes

Run a PHP syntax check on the plugin with:

```bash
find leon-popup-gate-affpairs -name "*.php" -print0 | xargs -0 -n1 php -l
```

Refer to `leon-popup-gate-affpairs/README.md` for full usage and configuration details.

## Why you cannot open a Pull Request from here

This workspace only contains a local Git repository without any remotes configured. A
GitHub/GitLab pull request requires pushing a branch to a remote repository first, but
because no remote origin is defined in this container you cannot run `git push`, and
therefore a PR cannot be created directly from this environment. To open a PR you would
need to clone this repository into your own account, add a remote pointing to that
account, push your branch, and then create the PR on the hosting service.
