# CRM System Documentation

## 🌐 Homepage

### Navigation Bar
The top navigation bar includes quick access to the main modules:
- **Accounts** – Manage company accounts.
- **Projects** – Track projects linked to accounts.
- **Tasks** – Manage tasks and assignments.
- **Reports** – Generate and view custom reports.
- **Price Offer** – Handle price quotations and offers.
- **Contacts** – Manage individual contacts.

### Homepage Content
- **Quick Analysis**
  - Dashboard-style stats (e.g., total accounts, ongoing projects, pending tasks).
  - Sales pipeline summary.
  - Activity overview.
- **Messages for Workers**
  - Admin can push global announcements or task reminders.
  - Worker-to-worker communication feed.

### Notifications Section
Placed **below the navigation bar and above the stats/analysis**:
- **Visiting Time Table** – Upcoming scheduled visits.
- **Late Visits** – Overdue visits that require follow-up.
- **Last Added Projects** – Display recently added or updated projects.
- **Messages from Admin** – Highlight important alerts and directives.

---

## 🏢 Accounts (Company Profiles)

### Overview
- Displays a **table of existing accounts** with:
  - Account Code
  - Account Name
  - Phone
  - Email
  - City
  - Country
  - Related Projects Count
  - Related Contacts Count

### Account Creation Form
A well-structured form for creating new accounts:

- **Top Section**
  - **Code** (disabled, auto-generated)
  - **Account Name** (editable)

- **Details Section**
  - Project Name (initial project for the account)
  - Account Phone
  - Email
  - Address (Google Maps link integration)
  - Region
  - City
  - Country
  - Related Contacts (dropdown sourced from **Contacts** module; can add/detach contacts)

### Related Contacts (Table)
When viewing an account, a **related contacts table** appears showing:
- Contact Name
- Phone Number
- Address (with Google Maps link)
- Department
- Job Title
- Relationship type (e.g., Primary, Secondary)

### Related Projects (Section)
Each account includes a **related projects list** and option to create new projects.

#### Create Project Form:
- Project Name
- Address (Google Maps link)
- Start Date
- Closing Date
- Contact attached to project (from Contacts module)
- Feedback/Notes
- **Need a Visit** (Yes/No toggle)
  - If **Yes** → additional fields appear:
    - Visit Date
    - Visit Reason
- Project Phase (dropdown: Planning, Execution, Monitoring, Closure)
- Project State (dropdown: Pre-started, Started, Finished)

---

## 📂 Projects

### Projects Overview
- Table of all projects, with columns:
  - Project Name
  - Related Account
  - Contact Person
  - Start Date
  - Closing Date
  - Project Phase
  - Project State
  - Need a Visit (Yes/No)
  - Feedback Summary

### Project Details
Each project has a dedicated page with:
- Full project details (same as creation form).
- Attachments (documents, images, contracts).
- Activity log (updates, assigned tasks, notes).
- Linked Contacts.
- Linked Tasks.

---

## 📋 Tasks & Reports

### Tasks
- Table view of all tasks:
  - Task Name
  - Assigned To
  - Related Project
  - Priority (High/Medium/Low)
  - Due Date
  - Status (Pending, In Progress, Done)

- Task Creation Form:
  - Task Title
  - Description
  - Assign To (dropdown of users)
  - Related Project
  - Priority
  - Due Date
  - Attachments

### Reports
- Predefined reports:
  - Task completion rates
  - Overdue tasks
  - Worker performance (tasks closed vs pending)
  - Project progress summaries
- Export options: PDF, Excel

---

## 💰 Price Offer (Quotations)

### Price Offer List
- Table showing:
  - Offer Code
  - Client/Account
  - Project
  - Offer Date
  - Total Amount
  - Status (Draft, Sent, Accepted, Rejected)

### Create Price Offer
Form fields:
- Offer Code (auto-generated)
- Related Account
- Related Project
- Itemized Products/Services:
  - Item Name
  - Quantity
  - Unit Price
  - Subtotal
- Taxes / Discounts
- Total
- Notes / Terms
- Status dropdown (Draft, Sent, Accepted, Rejected)

---

## 👥 Contacts

### Contacts Overview
- Table of all contacts:
  - Contact Name
  - Phone Number
  - Email
  - Department
  - Job Title
  - Related Accounts
  - Related Projects

### Contact Creation Form
- Contact Name
- Phone Number
- Email
- Department
- Job Title
- Address (Google Maps link)
- Related Accounts (multi-select dropdown)
- Related Projects (multi-select dropdown)

### Contact Details
- Shows profile info.
- Related accounts & projects listed.
- Quick actions: Edit, Detach from account/project.

---
