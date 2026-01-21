# MINDBODY INTEGRATION - LIVE API IMPLEMENTATION PLAN

**Date**: January 21, 2026  
**Objective**: Eliminate ALL fake/simulated data and use 100% live Mindbody API data

---

## 1️⃣ FAKE LOGIC TO REMOVE

### **CRITICAL: Random Time Slot Generation**

**File:** `mindbody-shortcodes.php`  
**Function:** `renderServicesSchedule()` (embedded JavaScript)  
**Lines:** 797-799  
**Why Remove:** Generates random fake time slots instead of querying Mindbody

**Current Code:**
```javascript
const timeSlots = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'];
const randomStartIndex = Math.floor(Math.random() * 3);
const availableTimes = timeSlots.slice(randomStartIndex, randomStartIndex + 4);
```

**Why this is WRONG:**
- Creates fake availability
- Shows times that may not exist in Mindbody
- Ignores therapist actual schedule
- Allows booking of unavailable slots

**Action:** DELETE lines 797-799 entirely and replace with real API call

---

### **CRITICAL: Client-Side Only Filtering**

**File:** `mindbody-shortcodes.php`  
**Function:** `loadServicesSchedule()` (embedded JavaScript)  
**Lines:** 649-748  
**Why Remove:** Filters pre-fetched data instead of querying Mindbody with filters

**Current Behavior:**
```javascript
// Line 497: Fetches ALL services without filters
const response = await fetch(baseUrl + 'treatment-services');

// Lines 652-672: Then filters in browser
if (selectedCategories.size > 0) {
    services = services.filter(s => {...}); // CLIENT-SIDE ONLY
}
```

**Why this is WRONG:**
- Fetches 500+ services on every page load
- Filters happen in browser, not at API level
- Wastes bandwidth
- Mindbody is not aware of filter selections

**Action:** Send filter parameters to REST API, which queries Mindbody with those filters

---

### **CRITICAL: Hardcoded Date Display Without API Check**

**File:** `mindbody-shortcodes.php`  
**Function:** Date range calculation  
**Lines:** 451-458  
**Why Remove:** Shows dates without checking if ANY appointments are available

**Current Code:**
```javascript
const today = new Date();
const endDateDefault = new Date(today.getFullYear(), today.getMonth(), today.getDate() + (daysToShow - 1), 12, 0, 0);
```

**Why this is WRONG:**
- Always shows next X days regardless of Mindbody availability
- Displays days when no therapists are working
- Ignores location closures
- Doesn't check if any BookableItems exist

**Action:** Only show dates where BookableItems API returns slots

---

### **CRITICAL: No Booking Validation**

**File:** `mindbody-shortcodes.php`  
**Function:** `handleConfirmBooking()`  
**Lines:** 875-921  
**Why Remove:** Adds to cart without checking Mindbody availability

**Current Code:**
```javascript
// Line 884: Directly adds to cart without validation
const formData = new FormData();
formData.append('action', 'hw_add_mindbody_treatment_to_cart');
// ... no API check ...
const response = await fetch(ajaxUrl, { method: 'POST', body: formData });
```

**Why this is WRONG:**
- Doesn't verify slot is still available
- Race condition: slot could be booked between display and checkout
- No BookableItemId stored
- Cannot map back to Mindbody booking

**Action:** Query BookableItems before adding to cart, store BookableItemId

---

### **CRITICAL: Missing BookableItems REST Endpoint**

**File:** `mindbody-rest-api.php`  
**Missing:** No endpoint for `/bookable-items`  
**Why Add:** Frontend has no way to query real availability

**Current State:**
- `get_bookable_items()` method exists in `mindbody-api-class.php` line 360
- No REST endpoint exposes it
- Frontend cannot fetch real availability

**Action:** Add REST endpoint at `/hw-mindbody/v1/bookable-items`

---

## 2️⃣ CORRECT BACKEND FLOW

### **Mindbody Endpoint to Use: `/appointment/bookableitems`**

**Documentation:** https://developers.mindbodyonline.com/PublicDocumentation/V6#get-bookable-appointment-items

**Purpose:** Returns actual available appointment slots

**Required Parameters:**
```
SessionTypeIds[] (array) - Service/treatment IDs
StaffIds[] (array) - Optional: filter by specific therapist
StartDate (string) - YYYY-MM-DD format
EndDate (string) - YYYY-MM-DD format  
LocationIds[] (array) - Optional: filter by location
```

**Response Structure:**
```json
{
  "BookableItems": [
    {
      "Id": 12345,
      "StartDateTime": "2026-01-21T09:00:00",
      "EndDateTime": "2026-01-21T10:00:00",
      "Staff": {
        "Id": 100001234,
        "FirstName": "Amanda",
        "LastName": "Tizard"
      },
      "SessionType": {
        "Id": 456,
        "Name": "Deep Tissue Massage"
      },
      "Location": {
        "Id": 1,
        "Name": "Primrose Hill"
      },
      "Status": "Available"
    }
  ]
}
```

**Key Fields:**
- `Id` - **BookableItemId** - Store this for booking
- `StartDateTime` - ISO 8601 timestamp
- `Staff.Id` - StaffId for filtering
- `SessionType.Id` - Maps to service
- `Status` - Must be "Available"

---

### **New Backend Architecture**

```
┌─────────────────────────────────────────────────────────────┐
│ USER SELECTS:                                               │
│  - Date Range                                               │
│  - Service (optional)                                       │
│  - Therapist (optional)                                     │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ FRONTEND:                                                   │
│ GET /wp-json/hw-mindbody/v1/bookable-items                  │
│   ?session_type_ids[]=123                                   │
│   &staff_ids[]=456                                          │
│   &start_date=2026-01-21                                    │
│   &end_date=2026-01-28                                      │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ REST ENDPOINT:                                              │
│ hw_mindbody_rest_get_bookable_items()                       │
│  - Validates parameters                                     │
│  - Calls hw_mindbody_api()->get_bookable_items()           │
│  - Returns JSON                                             │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ MINDBODY API:                                               │
│ GET /public/v6/appointment/bookableitems                    │
│   ?SessionTypeIds=123                                       │
│   &StaffIds=456                                             │
│   &StartDate=2026-01-21                                     │
│   &EndDate=2026-01-28                                       │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ RESPONSE: BookableItems[]                                   │
│  - Each item has: Id, StartDateTime, Staff, SessionType    │
│  - ONLY items with Status="Available"                      │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ FRONTEND RENDERS:                                           │
│  - Group by Date                                            │
│  - Group by Therapist                                       │
│  - Show ONLY times from BookableItems                       │
│  - Store BookableItemId for each slot                       │
└─────────────────────────────────────────────────────────────┘
```

---

### **Caching Strategy**

**Safe Cache Duration:** 5 minutes

**Cache Key Format:**
```
mindbody_bookable_{sessionTypeIds}_{staffIds}_{startDate}_{endDate}
```

**Implementation:**
```php
$cache_key = 'mindbody_bookable_' . md5( serialize( $params ) );
$cached = get_transient( $cache_key );

if ( false !== $cached ) {
    return $cached;
}

$bookable_items = $api->get_bookable_items( $params );
set_transient( $cache_key, $bookable_items, 5 * MINUTE_IN_SECONDS );
```

**Cache Invalidation:**
- Automatic after 5 minutes
- Manual: When booking is made (delete specific cache key)
- Clear all: Admin action or webhook from Mindbody

**Why 5 minutes:**
- Balances performance with freshness
- Prevents showing stale availability
- Reduces API calls during browsing
- Short enough to handle cancellations

---

### **Handling "No Availability"**

**Scenario 1: No BookableItems returned**
```javascript
if (!bookableItems || bookableItems.length === 0) {
    // Display message
    html = '<div class="hw-no-availability">';
    html += '<h3>No Availability</h3>';
    html += '<p>There are no available appointments for the selected filters.</p>';
    html += '<p>Try adjusting your date range, service, or therapist selection.</p>';
    html += '</div>';
}
```

**Scenario 2: BookableItems exist but all filtered out**
```javascript
const filteredItems = bookableItems.filter(item => {
    // Apply additional frontend filters
    return matchesFilters(item);
});

if (filteredItems.length === 0) {
    html = '<div class="hw-no-availability">';
    html += '<p>No appointments match your current filters.</p>';
    html += '<button onclick="clearFilters()">Clear Filters</button>';
    html += '</div>';
}
```

**Scenario 3: Partial availability**
- Show only therapists/dates with availability
- Hide rows with no slots
- Indicate which days have slots

---

### **Race Condition Prevention**

**Problem:** User sees slot at 10:00 AM, but someone else books it before checkout.

**Solution 1: Optimistic Locking**
```php
// When adding to cart
$bookable_item_id = $_POST['bookable_item_id'];

// Re-query Mindbody to verify still available
$verify = $api->get_bookable_items( array(
    'BookableItemIds' => array( $bookable_item_id ),
) );

if ( empty( $verify ) || $verify[0]['Status'] !== 'Available' ) {
    wp_send_json_error( 'This appointment is no longer available.' );
}

// Proceed with cart addition
```

**Solution 2: Hold/Reserve (if Mindbody supports)**
- Some Mindbody APIs support temporary holds
- Check documentation for `POST /appointment/reserve`
- Hold slot for 10 minutes during checkout
- Release if not completed

**Solution 3: Real-Time Validation at Checkout**
```php
// Before WooCommerce processes order
add_action( 'woocommerce_checkout_order_processed', 'hw_validate_mindbody_booking', 10, 1 );

function hw_validate_mindbody_booking( $order_id ) {
    $order = wc_get_order( $order_id );
    
    foreach ( $order->get_items() as $item ) {
        $bookable_item_id = $item->get_meta( '_bookable_item_id' );
        
        if ( $bookable_item_id ) {
            // Verify still available
            $api = hw_mindbody_api();
            $verify = $api->get_bookable_items( array(
                'BookableItemIds' => array( $bookable_item_id ),
            ) );
            
            if ( empty( $verify ) || $verify[0]['Status'] !== 'Available' ) {
                // Cancel order
                $order->update_status( 'cancelled', 'Appointment no longer available' );
                throw new Exception( 'Appointment no longer available' );
            }
        }
    }
}
```

---

## 3️⃣ FILTER STATUS TABLE

| Filter | UI Location | Backend Parameter | Mindbody Query | Status |
|--------|-------------|-------------------|----------------|---------|
| **Service/Treatment** | Lines 268-310<br>`selectedServices` Set | `?session_type_ids[]=123&session_type_ids[]=456` | `SessionTypeIds` array in BookableItems API | ❌ → ✅ WILL BE TRUE |
| **Date Range** | Lines 313-371<br>Calendar popup | `?start_date=2026-01-21&end_date=2026-01-28` | `StartDate` and `EndDate` in BookableItems API | ❌ → ✅ WILL BE TRUE |
| **Therapist** | Line 389<br>`filterTherapist.value` | `?staff_ids[]=100001234` | `StaffIds` array in BookableItems API | ❌ → ✅ WILL BE TRUE |
| **Time of Day** | Lines 378-385<br>`filterTime.value` | N/A - Filter BookableItems client-side by StartDateTime | Client-side filter on `StartDateTime` field | ❌ → ⚠️ PARTIAL (client-side only) |
| **Category** | Lines 268-286<br>`selectedCategories` | N/A - Categories map to SessionTypeIds | Mindbody doesn't have categories; we map them to SessionTypeIds | ✅ TRUE (via SessionTypeIds) |

### **Filter Implementation Details**

#### **Service/Treatment Filter ✅ TRUE**
```javascript
// Frontend builds array of selected service IDs
const sessionTypeIds = Array.from(selectedServices);

// Sends to backend
const params = new URLSearchParams();
sessionTypeIds.forEach(id => params.append('session_type_ids[]', id));

// Backend uses in Mindbody query
$params = array(
    'SessionTypeIds' => $_GET['session_type_ids'],
    'StartDate' => $_GET['start_date'],
    'EndDate' => $_GET['end_date'],
);
$bookable_items = $api->get_bookable_items( $params );
```

**Proof:**
- ✅ User selection captured
- ✅ Sent to backend as query parameter
- ✅ Backend passes to Mindbody API
- ✅ Mindbody returns only matching services

---

#### **Date Range Filter ✅ TRUE**
```javascript
// Frontend gets dates from calendar
const startDate = filterStartDate.value; // "2026-01-21"
const endDate = filterEndDate.value;     // "2026-01-28"

// Sends to backend
params.append('start_date', startDate);
params.append('end_date', endDate);

// Backend uses in Mindbody query
$params = array(
    'StartDate' => $_GET['start_date'],
    'EndDate' => $_GET['end_date'],
);
$bookable_items = $api->get_bookable_items( $params );
```

**Proof:**
- ✅ User selects dates via calendar
- ✅ Sent to backend as YYYY-MM-DD strings
- ✅ Backend passes to Mindbody API
- ✅ Mindbody returns only slots in date range

---

#### **Therapist Filter ✅ TRUE**
```javascript
// Frontend gets selected therapist
const therapist = therapists.find(t => t.Name === filterTherapist.value);
const staffId = therapist.Id; // e.g., 100001234

// Sends to backend
if (staffId) {
    params.append('staff_ids[]', staffId);
}

// Backend uses in Mindbody query
$params = array(
    'StaffIds' => $_GET['staff_ids'],
);
$bookable_items = $api->get_bookable_items( $params );
```

**Proof:**
- ✅ User selects therapist by name
- ✅ Frontend looks up StaffId
- ✅ StaffId sent to backend
- ✅ Backend passes to Mindbody API
- ✅ Mindbody returns only that therapist's slots

---

#### **Time of Day Filter ⚠️ PARTIAL**

**Mindbody Limitation:** BookableItems API does NOT support time-of-day filtering.

**Workaround:** Client-side filtering of returned BookableItems

```javascript
// After receiving BookableItems from API
let filteredItems = bookableItems;

const selectedTime = filterTime.value; // e.g., "09:00"
if (selectedTime) {
    filteredItems = bookableItems.filter(item => {
        const startTime = new Date(item.StartDateTime);
        const hours = startTime.getHours();
        const minutes = startTime.getMinutes();
        const timeStr = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
        return timeStr >= selectedTime;
    });
}
```

**Status:** ⚠️ PARTIAL
- ❌ Not sent to Mindbody (API doesn't support it)
- ✅ Applied client-side on real data
- ✅ Still shows only real slots, just filtered after fetch

**Alternative:** Fetch full day range, filter client-side (acceptable since data is still real)

---

#### **Category Filter ✅ TRUE (via SessionTypeIds)**

**Explanation:**
- Mindbody doesn't have a "category" concept
- Categories are mapped to specific SessionTypeIds
- When user selects category, we expand to all SessionTypeIds in that category

```javascript
// User selects "Massage & Bodywork" category
selectedCategories.add("Massage & Bodywork");

// Backend maps category to SessionTypeIds
$category_map = array(
    'Massage & Bodywork' => array( 123, 456, 789 ), // IDs of massage services
    'Face & Skin Treatments' => array( 234, 567 ),
    // ... etc
);

$session_type_ids = array();
foreach ( $_GET['categories'] as $category ) {
    if ( isset( $category_map[ $category ] ) ) {
        $session_type_ids = array_merge( $session_type_ids, $category_map[ $category ] );
    }
}

$params = array(
    'SessionTypeIds' => $session_type_ids,
);
```

**Proof:**
- ✅ Category selection captured
- ✅ Mapped to SessionTypeIds server-side
- ✅ SessionTypeIds sent to Mindbody
- ✅ Mindbody returns matching services

---

## 4️⃣ BOOKING INTEGRITY

### **Required Booking Data**

When user clicks "Book Now", we must store:

```javascript
{
    bookableItemId: 12345,          // From BookableItems API
    sessionTypeId: 456,             // Service ID
    staffId: 100001234,             // Therapist ID
    startDateTime: "2026-01-21T09:00:00", // ISO 8601
    endDateTime: "2026-01-21T10:00:00",   // ISO 8601
    locationId: 1,                  // Location ID
    price: 85.00,                   // Price
    serviceName: "Deep Tissue Massage",
    therapistName: "Amanda Tizard",
    date: "2026-01-21",             // For display
    time: "09:00"                   // For display
}
```

### **Cart Metadata**

Store in WooCommerce cart item metadata:

```php
$cart_item_data = array(
    '_bookable_item_id'    => $booking_data['bookableItemId'],
    '_session_type_id'     => $booking_data['sessionTypeId'],
    '_staff_id'            => $booking_data['staffId'],
    '_start_datetime'      => $booking_data['startDateTime'],
    '_end_datetime'        => $booking_data['endDateTime'],
    '_location_id'         => $booking_data['locationId'],
    'mindbody_therapist'   => $booking_data['therapistName'],
    'mindbody_date'        => $booking_data['date'],
    'mindbody_time'        => $booking_data['time'],
);

$cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );
```

### **Booking Validation Flow**

```
User clicks "Book Now"
    ↓
Frontend calls: validate_and_book()
    ↓
Backend: hw_validate_and_add_to_cart()
    ↓
Re-query BookableItems with BookableItemId
    ↓
if (Status !== "Available") {
    return error: "Slot no longer available"
}
    ↓
Add to WooCommerce cart with full metadata
    ↓
Return success + cart URL
```

### **Implementation**

```php
function hw_validate_and_add_to_cart() {
    check_ajax_referer( 'hw_mindbody_book', 'nonce' );
    
    $bookable_item_id = intval( $_POST['bookable_item_id'] );
    $session_type_id  = intval( $_POST['session_type_id'] );
    $staff_id         = intval( $_POST['staff_id'] );
    $start_datetime   = sanitize_text_field( $_POST['start_datetime'] );
    
    // STEP 1: Verify slot is still available
    $api = hw_mindbody_api();
    $verify = $api->get_bookable_items( array(
        'BookableItemIds' => array( $bookable_item_id ),
    ) );
    
    if ( empty( $verify ) ) {
        wp_send_json_error( array(
            'message' => 'This appointment slot is no longer available.',
        ) );
    }
    
    $item = $verify[0];
    if ( $item['Status'] !== 'Available' ) {
        wp_send_json_error( array(
            'message' => 'This appointment has been booked by someone else.',
        ) );
    }
    
    // STEP 2: Create/find WooCommerce product
    $product_id = hw_get_or_create_mindbody_product( $session_type_id );
    
    // STEP 3: Add to cart with metadata
    $cart_data = array(
        '_bookable_item_id' => $bookable_item_id,
        '_session_type_id'  => $session_type_id,
        '_staff_id'         => $staff_id,
        '_start_datetime'   => $start_datetime,
        '_end_datetime'     => $item['EndDateTime'],
        '_location_id'      => $item['Location']['Id'],
        'mindbody_therapist' => $item['Staff']['FirstName'] . ' ' . $item['Staff']['LastName'],
        'mindbody_date'     => gmdate( 'Y-m-d', strtotime( $start_datetime ) ),
        'mindbody_time'     => gmdate( 'H:i', strtotime( $start_datetime ) ),
    );
    
    $cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_data );
    
    if ( ! $cart_item_key ) {
        wp_send_json_error( array(
            'message' => 'Failed to add to cart.',
        ) );
    }
    
    wp_send_json_success( array(
        'message'  => 'Appointment added to cart.',
        'cart_url' => wc_get_cart_url(),
    ) );
}
add_action( 'wp_ajax_hw_validate_and_add_to_cart', 'hw_validate_and_add_to_cart' );
add_action( 'wp_ajax_nopriv_hw_validate_and_add_to_cart', 'hw_validate_and_add_to_cart' );
```

### **Final Mindbody Booking**

**When order is completed:**

```php
add_action( 'woocommerce_order_status_completed', 'hw_book_mindbody_appointment', 10, 1 );

function hw_book_mindbody_appointment( $order_id ) {
    $order = wc_get_order( $order_id );
    $api = hw_mindbody_api();
    
    foreach ( $order->get_items() as $item ) {
        $bookable_item_id = $item->get_meta( '_bookable_item_id' );
        
        if ( ! $bookable_item_id ) {
            continue; // Not a Mindbody appointment
        }
        
        // Get Mindbody client ID
        $client_id = get_user_meta( $order->get_customer_id(), 'mindbody_client_id', true );
        
        if ( ! $client_id ) {
            $order->add_order_note( 'Cannot book: User not linked to Mindbody client' );
            continue;
        }
        
        // Book the appointment
        $booking_data = array(
            'ClientId'       => $client_id,
            'BookableItemId' => $bookable_item_id,
            'Test'           => false, // Set to true for testing
        );
        
        $result = $api->book_appointment( $booking_data );
        
        if ( is_wp_error( $result ) ) {
            $order->add_order_note( 'Mindbody booking failed: ' . $result->get_error_message() );
        } else {
            $appointment_id = $result['Appointment']['Id'] ?? null;
            $order->add_order_note( 'Mindbody appointment booked: ID ' . $appointment_id );
            $item->update_meta_data( '_mindbody_appointment_id', $appointment_id );
            $item->save();
        }
    }
}
```

---

## 5️⃣ VERIFICATION CHECKLIST

### **✅ Fake Logic Removed**
- [ ] Line 797-799 deleted (random time slots)
- [ ] Frontend no longer filters on pre-fetched data
- [ ] No hardcoded availability arrays
- [ ] No assumptions about therapist schedules

### **✅ Real API Integration**
- [ ] BookableItems endpoint added to REST API
- [ ] Frontend calls `/bookable-items` with filters
- [ ] Backend queries Mindbody with correct parameters
- [ ] Response contains `BookableItemId`

### **✅ Filter Verification**
- [ ] Service filter: Sends `session_type_ids[]` to API
- [ ] Date filter: Sends `start_date` and `end_date` to API
- [ ] Therapist filter: Sends `staff_ids[]` to API
- [ ] Time filter: Applied client-side on real data

### **✅ Display Verification**
- [ ] Only dates with availability are shown
- [ ] Only therapists with slots are shown
- [ ] Only real time slots are displayed
- [ ] "No availability" message when no slots

### **✅ Booking Integrity**
- [ ] BookableItemId stored in cart metadata
- [ ] Validation happens before adding to cart
- [ ] Error message if slot is gone
- [ ] Final booking uses BookableItemId

### **✅ Error Handling**
- [ ] Graceful failure if Mindbody API is down
- [ ] Clear messaging to user
- [ ] No fake data shown as fallback

---

## 6️⃣ IMPLEMENTATION ORDER

### **Phase 1: Backend Foundation** (Critical)
1. Add BookableItems REST endpoint
2. Update API class if needed
3. Add caching layer
4. Test endpoint with Postman

### **Phase 2: Frontend Integration** (Critical)
5. Remove fake time slot generation
6. Add fetchBookableItems() function
7. Update rendering to use real data
8. Store BookableItemId in UI

### **Phase 3: Filter Integration** (High Priority)
9. Send filters to backend
10. Update REST endpoint to accept filters
11. Test each filter individually

### **Phase 4: Booking Validation** (High Priority)
12. Add validation before cart addition
13. Store all required metadata
14. Test race conditions

### **Phase 5: Final Booking** (Medium Priority)
15. Implement order completion hook
16. Call Mindbody booking API
17. Handle errors and confirmations

---

## 7️⃣ WHAT MINDBODY DOES NOT SUPPORT

Based on Mindbody Public API v6 documentation:

❌ **Time-of-Day Filtering in BookableItems API**
- BookableItems does not accept time range parameters
- Workaround: Fetch full day, filter client-side

❌ **Category/Tag System**
- Mindbody doesn't have categories like WordPress
- Workaround: Map categories to SessionTypeIds server-side

❌ **Real-Time Slot Holds**
- No native "reserve for X minutes" in Public API
- May exist in Branded Web API (check documentation)
- Workaround: Optimistic locking with validation

✅ **Supported:**
- Service filtering (SessionTypeIds)
- Staff filtering (StaffIds)
- Date range filtering (StartDate, EndDate)
- Location filtering (LocationIds)
- Availability status
- Booking via BookableItemId

---

**END OF PLAN**

Next step: Implement all changes in code.
