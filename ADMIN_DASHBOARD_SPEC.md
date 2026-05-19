# Admin Dashboard Spec (Phase 1)

## Goal
Create an admin dashboard to manage the website and view booking appointments across three phases.

## Location
- Admin dashboard URL: /admin-pannubai
- Initial implementation: /admin-pannubai/index.html (with shared assets from existing css/js)

## Primary Users
- Admin/staff users managing bookings and site content.

## Core Sections
1. **Overview KPIs**
   - Total bookings (all CSVs)
   - Upcoming bookings (today+future)
   - Completed bookings (past)
   - Cancellation rate (if data supports it)

2. **Bookings Table**
   - Columns: Date, Name, Contact, Service, Time/Slot, Status
   - Filters: Date range, Status
   - Search by name or contact

3. **Trends**
   - Bookings per day (sparkline/mini chart)
   - Most requested services

4. **Quick Actions**
   - Add booking (link to existing form or placeholder)
   - Export CSV (download filtered data)

5. **Site Controls (basic)**
   - Update announcement banner text (client-side only for now)
   - Toggle visibility for key sections (e.g., testimonials, gallery)

## Data Source
- Read all CSVs in /data/bookings_*.csv
- Parse client-side and aggregate

## Navigation
- Add Admin link to header/footer (visible only in admin page for now)

## Visual Style
- Use existing brand colors and typography from css/styles.css
- Clean layout with cards and a data table

## Phases
- **Phase 1**: Spec document (this file)
- **Phase 2**: Build layout + navigation + static placeholders
- **Phase 3**: Parse CSV data and wire KPIs, table, filters
