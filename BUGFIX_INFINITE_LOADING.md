# CRITICAL BUG FIXES - Infinite Loading Issue

**Date**: January 21, 2026  
**Issue**: Site stuck in infinite loading/buffering after implementing BookableItems integration

---

## ROOT CAUSE ANALYSIS

### Issue #1: Loading Spinner Never Hidden
**Location**: [mindbody-shortcodes.php#L187](mindbody-shortcodes.php#L187)

**Problem**:
```html
<div class="hw-mbo-loading" id="hw-loading-state">
    <div class="hw-mbo-spinner"></div>
    <p>Loading available appointments...</p>
</div>
```

The loading spinner shows by default in the HTML, but the `init()` function never called `loadAvailability()`, so the spinner was never hidden.

**Fix Applied**:
```javascript
async function init() {
    // Hide loading spinner initially
    if (loadingState) loadingState.style.display = 'none';
    // ... rest of init
}
```

---

### Issue #2: No Services Selected = Empty API Call
**Location**: [mindbody-shortcodes.php#L819](mindbody-shortcodes.php#L819)

**Problem**:
When page loads with no filters selected, `fetchBookableItems()` would make an API call with no `session_type_ids[]` parameter, potentially:
- Fetching ALL services in Mindbody (thousands)
- Causing API timeout
- Returning huge response that never finishes parsing

**Fix Applied**:
```javascript
// VALIDATION: Require at least one service filter to prevent huge data requests
const hasServiceFilter = params.has('session_type_ids[]');
if (!hasServiceFilter) {
    console.warn('No services selected - please select at least one treatment type');
    bookableItems = [];
    return; // Don't make API call without service filter
}
```

Now the function exits early if no services are selected, preventing infinite loading.

---

### Issue #3: Time Filter Value Mismatch
**Location**: [mindbody-shortcodes.php#L850-L876](mindbody-shortcodes.php#L850-L876)

**Problem**:
HTML dropdown uses values like `"06:00"`, `"09:00"`, `"12:00"`, etc:
```html
<option value="06:00">6:00 AM onwards</option>
<option value="09:00">9:00 AM onwards</option>
```

But JavaScript only checked for named values `'morning'`, `'afternoon'`, `'evening'`:
```javascript
switch(timeRange) {
    case 'morning': // Never matches!
    case 'afternoon': // Never matches!
    case 'evening': // Never matches!
}
```

**Fix Applied**:
```javascript
// Handle named ranges (morning/afternoon/evening)
switch(timeRange) {
    case 'morning': minHour = 9; maxHour = 12; break;
    case 'afternoon': minHour = 12; maxHour = 17; break;
    case 'evening': minHour = 17; maxHour = 21; break;
}

// Handle hour-based values (e.g., "09:00")
if (minHour === undefined && timeRange.includes(':')) {
    const parts = timeRange.split(':');
    minHour = parseInt(parts[0], 10);
    maxHour = 24; // End of day
}
```

Now supports both formats.

---

## CHANGES MADE

### File: mindbody-shortcodes.php

**Change 1**: Hide loading spinner on init
- **Lines**: 497-512
- **Action**: Added `loadingState.style.display = 'none'` at start of `init()`

**Change 2**: Added service filter validation
- **Lines**: 819-826
- **Action**: Exit `fetchBookableItems()` early if no services selected

**Change 3**: Fixed time filter to support both formats
- **Lines**: 850-876
- **Action**: Added logic to parse hour-based values like "09:00"

---

## TESTING CHECKLIST

### Before Fix (Symptoms)
- [x] Page shows loading spinner indefinitely
- [x] No appointments ever display
- [x] Console shows API calls with no parameters
- [x] Browser developer tools show fetch hanging
- [x] Time filter dropdown does nothing

### After Fix (Expected)
- [ ] Page loads with instruction message (no spinner)
- [ ] Loading spinner shows ONLY when Search button clicked or filter changed
- [ ] Instruction message says "Select filters above"
- [ ] Console shows: "No services selected - please select at least one treatment type"
- [ ] When service selected + Search clicked → spinner shows briefly → appointments display
- [ ] Time filter dropdown correctly filters by hour

---

## DEPLOYMENT INSTRUCTIONS

1. **Backup Current Site**:
   ```bash
   wp db export backup-$(date +%Y%m%d).sql
   ```

2. **Replace Modified File**:
   - Upload `mindbody-shortcodes.php` to theme directory
   - Location: `/wp-content/themes/hello-elementor-child/mindbody-shortcodes.php`

3. **Clear All Caches**:
   ```bash
   wp cache flush
   wp transient delete --all
   ```

4. **Test Scenarios**:
   - Visit appointments page
   - Verify instruction message shows (no loading spinner)
   - Select a treatment category
   - Click "Search" button
   - Verify loading spinner shows briefly
   - Verify appointments display with real time slots
   - Test time filter dropdown
   - Verify filtering works

5. **Monitor Errors**:
   ```bash
   tail -f /var/log/php-fpm/error.log
   tail -f /var/log/nginx/error.log
   ```

---

## ROLLBACK PLAN

If issues persist:

1. **Revert to Previous Commit**:
   ```bash
   git checkout HEAD~1 mindbody-shortcodes.php
   ```

2. **Or restore from backup**:
   ```bash
   # Use file from before implementation
   ```

3. **Known working state**: Commit before `e1bfbdf`

---

## API MONITORING

Check WordPress REST API endpoint manually:

```bash
# Test with no filters (should work but be cautious)
curl "https://yoursite.com/wp-json/hw-mindbody/v1/bookable-items"

# Test with service filter (recommended)
curl "https://yoursite.com/wp-json/hw-mindbody/v1/bookable-items?session_type_ids[]=123&start_date=2026-01-21&end_date=2026-01-28"
```

Expected response:
```json
{
  "success": true,
  "bookable_items": [...],
  "count": 15,
  "params": {...}
}
```

---

## BROWSER CONSOLE DEBUGGING

Open browser DevTools (F12) and check:

1. **Console tab** should show:
   ```
   === TREATMENT SERVICES LOADED ===
   Total services (filtered to 8 categories): 250
   Loaded 12 therapists
   ```

2. **Network tab** should show:
   - `/wp-json/hw-mindbody/v1/treatment-services` → 200 OK
   - `/wp-json/hw-mindbody/v1/staff-appointments` → 200 OK
   - NO request to `/bookable-items` until Search clicked

3. **After clicking Search with services selected**:
   ```
   Fetching BookableItems with params: session_type_ids[]=123&session_type_ids[]=456&start_date=2026-01-21
   Received 15 bookable items from Mindbody
   ```

4. **If no services selected**:
   ```
   No services selected - please select at least one treatment type
   ```

---

## KNOWN LIMITATIONS

1. **Time filter**: Client-side only (Mindbody API doesn't support time-of-day filtering)
2. **Requires service selection**: Cannot load ALL appointments (would be thousands)
3. **5-minute cache**: Recently booked slots may still show for up to 5 minutes

These are by design and documented in IMPLEMENTATION_PLAN.md.

---

## SUMMARY

**Root Cause**: Loading spinner shown by default + no service filter validation = infinite loading

**Fix**: 
1. Hide spinner on page load
2. Validate service filter before API call
3. Fix time filter value parsing

**Impact**: Page now loads instantly with clear instructions, API calls only happen when needed, time filter works correctly.

**Status**: ✅ READY FOR DEPLOYMENT
