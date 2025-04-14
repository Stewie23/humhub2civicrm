# humhub2civicrm

**humhub2civicrm** is a connector module that synchronizes HumHub profile fields with CiviCRM groups. The Extended Contact Matcher must be installed on CiviCRM ([https://github.com/systopia/de.systopia.xcm](https://github.com/systopia/de.systopia.xcm)).

This module supports flexible newsletter and opt-in scenarios by linking HumHub checkboxes or profile values to CiviCRM group membership—fully automated on profile save.

---

⚠️ **Note**: This module is in early development. While group joining and leaving functionality is working, logging is currently minimal and not yet user-friendly. The connection test is basic, profile field handling is limited, and matcher configuration is currently hardcoded. A dedicated log viewer and admin tools are planned.

---

## 🧹 Features

- 🔁 **Auto-Sync on Profile Save**  
  Whenever a HumHub user updates their profile, an API call to the Extended Contact Matcher (XCM) in CiviCRM is made. Fields to send to CiviCRM can be configured.

- 📬 **Newsletter/Opt-in Mapping**  
  Profile fields (e.g., checkboxes like `receiveNewsletter`) can be mapped to CiviCRM group joins/leaves. Supports "double opt-in" logic via separate `groupJoin` and `groupLeave`.

- 🔧 **Flexible Admin Configuration**  
  Easily configure CiviCRM API credentials and field-to-group mappings via a backend settings form.

- 🔗 **CiviCRM Matcher Integration**  
  Currently uses [`xcm_profile=HumHubMatcher`](https://github.com/systopia/de.systopia.xcm) to match or create contacts in CiviCRM.

---

## ⚙️ How It Works

1. On profile update (`onProfileUpdate`), the module sends the user's profile information to CiviCRM (`Contact.getorcreate`). A suitable matching profile must be configured.
2. If successful, it retrieves or creates a contact.
3. For each configured profile field:
   - If the field value is truthy → the contact is **added** to `groupJoin`
   - If the field is falsy → the contact is **removed** from `groupLeave`

This lets you implement opt-in/out flows for newsletters, campaigns, or interest-based tagging in CiviCRM.

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

#### Newsletter / Opt-in Groups
Define group assignments based on boolean profile fields (e.g., checkboxes for subscriptions):

| field               | groupJoin | groupLeave | description                         |
|--------------------|-----------|------------|-------------------------------------|
| `receiveUpdates`   | `102`     | `103`      | Different groups for opt-in/out     |
| `eventOptIn`       | `201`     | `202`      | Different groups for opt-in/out     |
| `specialNotice`    | `301`     | `301`      | Same group for join/leave (logging) |

- If a profile field is checked (`true`), the contact is **added** to `groupJoin`
- If unchecked (`false`), the contact is **removed** from `groupLeave`
- `groupJoin` can be left the same as `groupLeave` for simple setups, where no contact moving happens in civi
  
### 🗑️ Deleted User Behavior *(Config implemented, functionality pending)*

Choose how to propagate deleted users from HumHub to CiviCRM:

- **Soft delete (default)**: Removes user from all HumHub-related groups and adds them to a dedicated "Deleted Users" group for manual review.
- **Anonymize**: Scrubs name, email, and phone from the CiviCRM contact (but retains the record).
- **Hard delete**: Completely removes the contact from CiviCRM — use with caution.

You can define the CiviCRM **Group ID** to assign for soft-deleted users via a dedicated input field.

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
