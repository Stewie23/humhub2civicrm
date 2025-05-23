# humhub2civicrm

**humhub2civicrm** is a connector module that synchronizes HumHub profile fields with CiviCRM groups. The Extended Contact Matcher must be installed on CiviCRM ([https://github.com/systopia/de.systopia.xcm](https://github.com/systopia/de.systopia.xcm)).

This module supports flexible newsletter and opt-in scenarios by linking HumHub checkboxes or profile values to CiviCRM group membership—fully automated on profile save.

---

⚠️ **Note**: This module is in early development. While group joining and leaving functionality is working, logging is currently minimal and not yet user-friendly. The connection test is basic, profile field handling is limited, and matcher configuration is currently hardcoded. A dedicated log viewer and admin tools are planned.

---

## Features
### Profile Sync
Automatically sync selected HumHub profile fields to CiviCRM whenever a user updates their profile.

### Double Opt-in Newsletter Subscriptions
Profile fields mapped to newsletter subscriptions now use CiviCRM’s MailingEventSubscribe API for proper double opt-in handling.

### Configurable Deletion Behavior
On user deletion in HumHub (soft or hard), CiviCRM contacts can be moved to a group, anonymized, or deleted.

### Admin UI Configuration
All settings (CiviCRM API, field mappings, group IDs) can be configured via HumHub’s module admin panel.

---

## ⚙️ How It Works

1. On profile update, the module sends the user's data to CiviCRM using Contact.getorcreate.

2. Standard fields and configured mappings are included in the payload.

3. For newsletter fields:
   - If enabled, the module sends a `MailingEventSubscribe.create` request to initiate a proper double opt-in process in CiviCRM.
   - If the user deselects a newsletter checkbox in HumHub, they are removed from the corresponding CiviCRM mailing group via `GroupContact.delete`

4. On user deletion:
   - Handles both soft and hard deletions in HumHub and propagates the event to CiviCRM.
   - For soft deletes, the user is added to a configured "Deleted Users" group in CiviCRM.

---

## 🔧 Configuration

Configuration is available under:  
**Admin Panel → Modules → humhub2civicrm → Settings**

### 🔐 CiviCRM API Settings
- **`apiUrl`**: Full REST endpoint of your CiviCRM instance (e.g. `https://example.org/civicrm/ajax/rest`)
- **`apiKey`**: CiviCRM API key (bound to the user account used for API access)
- **`siteKey`**: CiviCRM site key (global configuration, usually from `civicrm.settings.php`)
- **`contactManagerProfile`**: Name of the XCM matcher profile used for contact syncing (e.g. `HumHubMatcher`)

### 🧩 Field Mappings

#### Standard Fields → Contact Fields
Use checkboxes in the settings form to choose which standard HumHub profile fields should be sent to CiviCRM. Currently supported:
- First name
- Last name
- Phone (work)
- Gender (auto-mapped to numeric CiviCRM values)

More field types (e.g. address, date of birth) are planned.

#### Newsletter 
Define group assignments based on boolean profile fields (e.g., checkboxes for subscriptions):

| field               | groupJoin | 
|--------------------|-----------|
| `receiveUpdates`   | `102`     | 
| `eventOptIn`       | `201`     |
| `specialNotice`    | `301`     | 

- If a profile field is checked (`true`), the contact is subscribed to the associated CiviCRM mailing group.
- If unchecked (`false`), the contact is **removed** from the Group.

  
### 🗑️ Deleted User Behavior *(Config implemented, functionality pending)*

Choose how to propagate deleted users from HumHub to CiviCRM:

- **Soft delete (default)**: Removes user from all HumHub-related groups and adds them to a dedicated "Deleted Users" group for manual review.
- **Anonymize**: Scrubs name, email, and phone from the CiviCRM contact (but retains the record).
- **Hard delete**: Completely removes the contact from CiviCRM — use with caution.

You can define the CiviCRM **Group ID** to assign for soft-deleted users via a dedicated input field.

Note: Currently, all deletions follow the soft delete logic on the CiviCRM side. Hard deletes and anonymization are not yet fully implemented.


---

## ✅ Requirements

- A working [CiviCRM](https://civicrm.org) installation
- The [de.systopia.xcm](https://github.com/systopia/de.systopia.xcm) extension installed and enabled in CiviCRM

---

## 🔍 Debugging / Logs

All API activity (calls, errors, group actions) is logged in two places:

- **Module-specific log file**: Located in `@runtime/logs/humhub2civicrm.log`. This includes API calls, contact sync details, payload data, and group updates.
- **HumHub's general log (`app.log`)**: Only critical errors and warnings are sent here to avoid clutter.

The payload sent to CiviCRM is logged as JSON for easy inspection. If a contact update fails, CiviCRM responses are also logged in full.

A dedicated admin-facing log viewer is planned for a future version.

---

## 🧪 Connection Test

In the module settings, you can run a connection test to verify that your CiviCRM API credentials are working.  
Improved feedback and diagnostics are planned.

---

## 👤 Maintainer

Module by [@Stewie23](https://github.com/Stewie23). Contributions and issues welcome.

---

## ⚖️ License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
