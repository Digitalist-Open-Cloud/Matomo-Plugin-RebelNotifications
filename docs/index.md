# Matomo Rebel Notifications Plugin

- **Requires**: Matomo >= 5.0.0
- **License**: GPL v3+

With an API-first approach with Rebel Notifications you could easily automate notifications in your Matomo-instances. You could also display many notifications at once, use HTML with notifications, etc.

## Status for tests

![matomo plugin tests](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-RebelNotifications/actions/workflows/matomo.yaml/badge.svg) ![semgrep oss scan](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-RebelNotifications/actions/workflows/semgrep.yaml/badge.svg) ![phpcs](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-RebelNotifications/actions/workflows/phpcs.yaml/badge.svg)

Tests is done with [Matomo GitHub Action Tests](https://github.com/Digitalist-Open-Cloud/Matomo-github-action-tests), which tests the plugin with Integration-tests against the least (8.3) and highest (8.5) supported PHP-version together with the least (5.0.0) and highest available version of Matomo.

## What is Rebel?

Rebel is short for RebelMetrics. RebelMetrics is Matomo on super charged batteries from Digitalist Open Cloud, with pre-configured dashboards, SQL-lab and more. We offer 1 month free trial for organizations and companies. If you are interested, email us at <cloud@digitalist.com> to book a demo.

## Description

With Rebel Notifications you can add notifications to your users, with a range of settings:

- Type of notification
- Use HTML (links, images etc.)
- Priority
- Etc.

Rebel Notifications are using the built in Notifications in Matomo and adds a UI to it to create custom notifications.

## Inspiration

This plugin was inspired by the [Admin Notification](https://plugins.matomo.org/AdminNotification) plugin by [Josh Brule](https://github.com/jbrule).

## Installation

Install the plugin as you normally install any Matomo plugin.

## Usage

After installation, a new menu item is visible in the admin part of Matomo - "Rebel Notifications".
At "Manage" you can add, edit and delete notifications.

When you add or change a notification, nothing is changed until you logout and login. The triggering event for the notifications is `Login.authenticate.successful` - which means that nothing updates until you login.

## Using Rebel Notifications with the console

### Create a notification

```sh
./console rebelnotifications:create --enabled --raw --title="My title" --message="This is the message in <strong>bold</strong>" --context=warning --priority=50 --type=persistent
```

#### Options

- `--enabled` - Set notification as enabled
- `--raw` - Allow limited HTML input in message (see allowed tags below)
- `--title` - Notification title (required)
- `--message` - Notification message (required)
- `--context` - Context: warning, info, success, error (required)
- `--priority` - Priority number (required)
- `--type` - Type: persistent, transitory (required)

### List notifications

#### All notifications

```sh
./console rebelnotifications:list
```

#### Enabled notifications

```sh
./console rebelnotifications:list --enabled
```

## Using RebelNotifications with Matomo API

Examples with curl.

### Create a notification

```sh
curl -X POST "https://MATOMO.URL/index.php" \
     -d "module=API" \
     -d "method=RebelNotifications.insertNotification" \
     -d "enabled=1" \
     -d "title=bar" \
     -d "message=foo is bar" \
     -d "context=warning" \
     -d "priority=25" \
     -d "type=persistent" \
     -d "raw=0" \
     -d "token_auth=A_SECURE_TOKEN" \
     -d "format=JSON"
```

### Edit a notification

```sh
curl -X POST "https://MATOMO.URL/index.php" \
     -d "module=API" \
     -d "method=RebelNotifications.updateNotification" \
     -d "id=24" \
     -d "enabled=1" \
     -d "title=bar" \
     -d "message=Changing the message" \
     -d "context=warning" \
     -d "priority=25" \
     -d "type=persistent" \
     -d "raw=0" \
     -d "token_auth=A_SECURE_TOKEN" \
     -d "format=JSON"
```

### Delete a notification

```sh
curl -X POST "https://MATOMO.URL/index.php" \
     -d "module=API" \
     -d "method=RebelNotifications.deleteNotification" \
     -d "id=24" \
     -d "token_auth=A_SECURE_TOKEN" \
     -d "format=JSON"
```

### List enabled notifications

```sh
curl -X POST "https://MATOMO.URL/index.php" \
     -d "module=API" \
     -d "method=RebelNotifications.getEnabledNotifications" \
     -d "token_auth=A_SECURE_TOKEN" \
     -d "format=JSON"
```

### List disabled notifications

```sh
curl -X POST "https://MATOMO.URL/index.php" \
     -d "module=API" \
     -d "method=RebelNotifications.getDisabledNotifications" \
     -d "token_auth=A_SECURE_TOKEN" \
     -d "format=JSON"
```

### List all notifications

```sh
curl -X POST "https://MATOMO.URL/index.php" \
     -d "module=API" \
     -d "method=RebelNotifications.getAllNotifications" \
     -d "token_auth=A_SECURE_TOKEN" \
     -d "format=JSON"
```

## API Methods

The plugin provides the following API methods:

| Method | Description |
|--------|-------------|
| `RebelNotifications.insertNotification` | Create a new notification |
| `RebelNotifications.updateNotification` | Update an existing notification |
| `RebelNotifications.deleteNotification` | Delete a notification |
| `RebelNotifications.getEnabledNotifications` | Get all enabled notifications |
| `RebelNotifications.getDisabledNotifications` | Get all disabled notifications |
| `RebelNotifications.getAllNotifications` | Get all notifications |

### Parameter Reference

| Parameter | Type | Description |
|-----------|------|-------------|
| `enabled` | int | 1 = enabled, 0 = disabled |
| `title` | string | Notification title |
| `message` | string | Notification message (supports HTML if raw=1) |
| `context` | string | warning, info, success, error |
| `priority` | int | Priority number (higher = more important) |
| `type` | string | persistent or transitory |
| `raw` | int | 1 = allow HTML (see allowed tags below), 0 = strip all HTML |

### Allowed HTML tags when using raw input

When `raw=1` (or `--raw` flag), the following HTML tags are allowed in the message:

- `<b>`, `<strong>` - Bold text
- `<i>`, `<em>` - Italic text
- `<a>` - Links
- `<h1>`, `<h2>`, `<h3>`, `<h4>`, `<h5>`, `<h6>` - Headings

All other HTML tags (including `<script>`, `<iframe>`, `<object>`, etc.) will be stripped for security.

## License

Copyright (C) Digitalist Open Cloud <cloud@digitalist.com>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <https://www.gnu.org/licenses/>.
