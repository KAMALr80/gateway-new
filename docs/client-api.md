# Gateway — Client API

**Version:** 1.0  
**Base URL:** `{GATEWAY_URL}/api`  
**Audience:** Flutter mobile app

---

## Overview

This API is the sole interface between the mobile app and the backend. The app never communicates directly with the ERP. All catalog data (products, categories, brands) is served from Redis cache. Orders are placed synchronously but dispatched to the ERP asynchronously via a queue.

---

## Authentication

Protected routes require a JWT bearer token obtained from `POST /api/auth/login`.

| Header | Value |
|---|---|
| `Authorization` | `Bearer {token}` |
| `Content-Type` | `application/json` |
| `Accept` | `application/json` |

**Unauthenticated response (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Token lifetime** is configured via `JWT_TTL` (default 1440 minutes / 24 hours). Tokens can be refreshed within the `JWT_REFRESH_TTL` window (default 20160 minutes / 14 days) using `POST /api/auth/refresh`.

---

## HTTP Status Codes

| Code | Meaning |
|---|---|
| `200` | Success |
| `201` | Resource created |
| `401` | Missing, invalid, or expired token |
| `403` | Forbidden — authenticated but not authorized |
| `404` | Resource not found |
| `422` | Validation error |
| `500` | Server error |

---

## Validation Errors

Failed validation returns `422` with field-level detail:

```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Endpoints

- [Authentication](#authentication-1)
  - [POST /api/auth/register](#post-apiauthregister)
  - [POST /api/auth/login](#post-apiauthlogin)
  - [POST /api/auth/refresh](#post-apiauthrefresh)
  - [POST /api/auth/validate](#post-apiauthvalidate)
  - [POST /api/auth/logout](#post-apiauthlogout)
- [Users](#users)
  - [GET /api/users/me](#get-apiusersme)
  - [PATCH /api/users/{id}](#patch-apiusersid)
- [Products](#products)
  - [GET /api/products](#get-apiproducts)
  - [GET /api/products/{id}](#get-apiproductsid)
- [Categories](#categories)
  - [GET /api/categories](#get-apicategories)
  - [GET /api/categories/{id}](#get-apicategoriesid)
- [Brands](#brands)
  - [GET /api/brands](#get-apibrands)
  - [GET /api/brands/{id}](#get-apibrandsid)
- [Orders](#orders)
  - [GET /api/orders](#get-apiorders)
  - [POST /api/orders](#post-apiorders)
  - [GET /api/orders/{id}](#get-apiordersid)
- [Addresses](#addresses)
  - [GET /api/addresses](#get-apiaddresses)
  - [POST /api/addresses](#post-apiaddresses)
  - [GET /api/addresses/{id}](#get-apiaddressesid)
  - [PATCH /api/addresses/{id}](#patch-apiaddressesid)
  - [DELETE /api/addresses/{id}](#delete-apiaddressesid)

---

## Authentication

### POST /api/auth/register

Registers a new user account. No authentication required. Accounts created via this endpoint are set to **pending** status and must be approved by an administrator before the user can log in.

#### Request Body

```json
{
  "name": "Jane Doe",
  "email": "jane.doe@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

| Field | Type | Required | Constraints | Description |
|---|---|---|---|---|
| `name` | string | Yes | max 255 | Full name. |
| `email` | string | Yes | Valid email, unique across all users | Email address. |
| `password` | string | Yes | min 8 characters | Password. |
| `password_confirmation` | string | Yes | Must match `password` | Password confirmation. |

#### Response `201`

```json
{
  "message": "Registration successful. Your account is pending approval before you can log in."
}
```

**Duplicate email response `422`:**
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

#### Notes

- Email uniqueness is enforced — registering with an existing email returns `422`
- The account is created with `approval_status = pending`. The user cannot log in until an administrator approves the account
- Users synced from the ERP via `POST /internal/contacts/sync` are pre-approved and do not need manual approval

---

### POST /api/auth/login

Authenticates a user and returns a JWT token. No authentication required.

#### Request Body

```json
{
  "email": "jane.doe@example.com",
  "password": "secret"
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | Yes | User's email address. |
| `password` | string | Yes | User's password. |

#### Response `200`

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 86400,
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane.doe@example.com",
    "phone": "+1-555-0100",
    "address": "123 Main Street, Springfield, IL",
    "erp_contact_id": 301
  }
}
```

| Field | Type | Description |
|---|---|---|
| `access_token` | string | JWT to use in `Authorization: Bearer` header. |
| `token_type` | string | Always `"bearer"`. |
| `expires_in` | integer | Token lifetime in seconds. |
| `user` | object | Authenticated user's profile. |

**Invalid credentials response `401`:**
```json
{
  "message": "Invalid credentials"
}
```

**Pending approval response `403`:**
```json
{
  "message": "Your account is pending approval."
}
```

---

### POST /api/auth/refresh

Issues a new JWT token using an existing (possibly expired but still within refresh window) token. No request body required — the current token is read from the `Authorization` header.

**Auth required:** Yes (current token, even if expired within refresh TTL)

#### Response `200`

Same shape as [login response](#post-apiauthlogin).

**Cannot refresh response `401`:**
```json
{
  "message": "Token cannot be refreshed"
}
```

#### Notes

- Tokens can be refreshed within `JWT_REFRESH_TTL` (default 14 days) from original issue time
- After a refresh, the old token is invalidated

---

### POST /api/auth/validate

Confirms the current token is valid and returns the authenticated user. Useful for app startup checks.

**Auth required:** Yes

#### Response `200`

```json
{
  "valid": true,
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane.doe@example.com",
    "phone": "+1-555-0100",
    "address": "123 Main Street, Springfield, IL",
    "erp_contact_id": 301
  }
}
```

---

### POST /api/auth/logout

Invalidates the current token.

**Auth required:** Yes

#### Response `200`

```json
{
  "message": "Logged out successfully"
}
```

---

## Users

### GET /api/users/me

Returns the authenticated user's profile along with their total order count.

**Auth required:** Yes

#### Response `200`

```json
{
  "id": 1,
  "name": "Jane Doe",
  "email": "jane.doe@example.com",
  "phone": "+1-555-0100",
  "address": "123 Main Street, Springfield, IL",
  "erp_contact_id": 301,
  "orders_count": 4
}
```

| Field | Type | Description |
|---|---|---|
| `id` | integer | Gateway user ID. |
| `name` | string | Full name. |
| `email` | string | Email address. |
| `phone` | string\|null | Phone number. |
| `address` | string\|null | Address. |
| `erp_contact_id` | integer\|null | Linked ERP contact ID. `null` if not yet synced from ERP. |
| `orders_count` | integer | Total number of orders placed by this user. |

---

### PATCH /api/users/{id}

Updates the authenticated user's profile. Users can only update their own profile — attempting to update another user's profile returns `403`.

**Auth required:** Yes

| Path Parameter | Type | Description |
|---|---|---|
| `id` | integer | Must match the authenticated user's ID. |

#### Request Body

All fields are optional. Only include fields to update.

```json
{
  "name": "Jane Smith",
  "email": "jane.smith@example.com",
  "phone": "+1-555-0199",
  "address": "456 Oak Avenue, Chicago, IL"
}
```

| Field | Type | Required | Constraints | Description |
|---|---|---|---|---|
| `name` | string | No | max 255 | Full name. |
| `email` | string | No | Valid email, unique across all users | Email address. |
| `phone` | string | No | max 20 | Phone number. |
| `address` | string | No | max 500 | Address string. |

#### Response `200`

```json
{
  "message": "Profile updated successfully",
  "user": {
    "id": 1,
    "name": "Jane Smith",
    "email": "jane.smith@example.com",
    "phone": "+1-555-0199",
    "address": "456 Oak Avenue, Chicago, IL"
  }
}
```

**Forbidden response `403`:**
```json
{
  "message": "Forbidden"
}
```

---

## Products

### GET /api/products

Returns a paginated list of active products. Supports filtering and sorting. Results are served from Redis cache keyed by a fingerprint of the query parameters.

By default, only top-level and grouped parent products are returned — child products (those that belong to a group) are hidden. Pass `include_children=true` to include them.

**Auth required:** Yes

#### Query Parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| `category_id` | integer | — | Filter by main category. |
| `sub_category_id` | integer | — | Filter by sub-category. |
| `brand_id` | integer | — | Filter by brand. |
| `type` | string | — | Filter by product type (e.g. `simple`, `variable`, `grouped`). |
| `in_stock` | boolean | — | When `true`, returns only in-stock products. |
| `search` | string | — | Full-text search across `name`, `sku`, and `description`. When `include_children` is `false`, a match on a child product returns the child's grouped parent instead. |
| `include_children` | boolean | `false` | When `false`, child products (those with a `parent_id`) are excluded from results. Pass `true` to include them. |
| `sort` | string | `name_asc` | Sort order. Options: `price_asc`, `price_desc`, `newest`, `name_asc`. |
| `per_page` | integer | `20` | Results per page. Maximum `100`. |
| `page` | integer | `1` | Page number. |

#### Response `200`

```json
{
  "data": [
    {
      "id": 12,
      "name": "Wireless Headphones X1",
      "slug": "wireless-headphones-x1",
      "type": "simple",
      "sku": "WH-X1-BLK",
      "regular_price": "149.99",
      "sale_price": "119.99",
      "current_price": "119.99",
      "on_sale": true,
      "in_stock": true,
      "stock_quantity": 50,
      "image": "https://cdn.example.com/products/wh-x1-main.jpg",
      "category": {
        "id": 3,
        "name": "Electronics",
        "slug": "electronics"
      },
      "sub_category": {
        "id": 7,
        "name": "Headphones",
        "slug": "headphones"
      },
      "brand": {
        "id": 2,
        "name": "Acme Corp",
        "slug": "acme-corp"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 84,
    "last_page": 5
  }
}
```

**Product object (list shape):**

| Field | Type | Description |
|---|---|---|
| `id` | integer | Gateway product ID. |
| `name` | string | Product display name. |
| `slug` | string | URL-safe name. |
| `type` | string | Product type (e.g. `simple`, `variable`). |
| `sku` | string\|null | Stock keeping unit. |
| `regular_price` | numeric | Base price. |
| `sale_price` | numeric\|null | Discounted price. `null` if no active sale. |
| `current_price` | numeric | The effective price: `sale_price` if set, otherwise `regular_price`. |
| `on_sale` | boolean | `true` when `sale_price` is set. |
| `in_stock` | boolean | Stock availability. |
| `stock_quantity` | integer\|null | Available units. `null` when `manage_stock` is `false`. |
| `image` | string\|null | Primary image URL. |
| `category` | object\|null | `{ id, name, slug }` |
| `sub_category` | object\|null | `{ id, name, slug }` |
| `brand` | object\|null | `{ id, name, slug }` |

**Meta object:**

| Field | Type | Description |
|---|---|---|
| `current_page` | integer | Current page number. |
| `per_page` | integer | Items per page. |
| `total` | integer | Total matching records. |
| `last_page` | integer | Total number of pages. |

---

### GET /api/products/{id}

Returns full detail for a single active product, including all images, description, and attributes. Served from Redis cache.

For grouped products (`type = "grouped"`), the `children` array is always included in the response.

For child products, pass `?with_siblings=1` to include the `parent` object and the `children` array of sibling products.

**Auth required:** Yes

| Path Parameter | Type | Description |
|---|---|---|
| `id` | integer | Gateway product ID. |

#### Query Parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| `with_siblings` | boolean | `false` | For child products: includes `parent` object and sibling `children[]` in the response. Has no effect on standalone products. |

#### Response `200` — standalone or grouped product

```json
{
  "id": 10,
  "name": "Wireless Headphones X1",
  "slug": "wireless-headphones-x1",
  "type": "grouped",
  "sku": null,
  "regular_price": "149.99",
  "sale_price": null,
  "current_price": "149.99",
  "on_sale": false,
  "in_stock": true,
  "stock_quantity": null,
  "image": "https://cdn.example.com/products/wh-x1-main.jpg",
  "category": { "id": 3, "name": "Electronics", "slug": "electronics" },
  "sub_category": null,
  "brand": { "id": 2, "name": "Acme Corp", "slug": "acme-corp" },
  "description": "Available in Black and White.",
  "short_description": "Premium wireless headphones.",
  "attributes": null,
  "images": [],
  "parent": null,
  "children": [
    {
      "id": 11,
      "name": "Wireless Headphones X1 — Black",
      "slug": "wireless-headphones-x1-black",
      "type": "simple",
      "sku": "WH-X1-BLK",
      "regular_price": "149.99",
      "sale_price": "119.99",
      "current_price": "119.99",
      "on_sale": true,
      "in_stock": true,
      "stock_quantity": 50,
      "image": "https://cdn.example.com/products/wh-x1-blk.jpg",
      "category": { "id": 3, "name": "Electronics", "slug": "electronics" },
      "sub_category": null,
      "brand": { "id": 2, "name": "Acme Corp", "slug": "acme-corp" }
    }
  ]
}
```

#### Response `200` — child product with `?with_siblings=1`

```json
{
  "id": 11,
  "name": "Wireless Headphones X1 — Black",
  "slug": "wireless-headphones-x1-black",
  "type": "simple",
  "sku": "WH-X1-BLK",
  "regular_price": "149.99",
  "sale_price": "119.99",
  "current_price": "119.99",
  "on_sale": true,
  "in_stock": true,
  "stock_quantity": 50,
  "image": "https://cdn.example.com/products/wh-x1-blk.jpg",
  "category": { "id": 3, "name": "Electronics", "slug": "electronics" },
  "sub_category": null,
  "brand": { "id": 2, "name": "Acme Corp", "slug": "acme-corp" },
  "description": "Full description.",
  "short_description": "Black variant.",
  "attributes": { "color": "Black" },
  "images": [
    {
      "url": "https://cdn.example.com/products/wh-x1-blk.jpg",
      "thumbnail": null,
      "alt": "Wireless Headphones X1 — Black",
      "is_primary": true
    }
  ],
  "parent": {
    "id": 10,
    "name": "Wireless Headphones X1",
    "slug": "wireless-headphones-x1"
  },
  "children": [
    {
      "id": 12,
      "name": "Wireless Headphones X1 — White",
      "slug": "wireless-headphones-x1-white",
      "type": "simple",
      "sku": "WH-X1-WHT",
      "regular_price": "149.99",
      "sale_price": null,
      "current_price": "149.99",
      "on_sale": false,
      "in_stock": true,
      "stock_quantity": 30,
      "image": "https://cdn.example.com/products/wh-x1-wht.jpg",
      "category": { "id": 3, "name": "Electronics", "slug": "electronics" },
      "sub_category": null,
      "brand": { "id": 2, "name": "Acme Corp", "slug": "acme-corp" }
    }
  ]
}
```

**Additional fields (detail only):**

| Field | Type | Description |
|---|---|---|
| `description` | string\|null | Full product description. |
| `short_description` | string\|null | Brief summary. |
| `attributes` | object\|null | Arbitrary key-value pairs (e.g. color, size). |
| `images` | array | All product images ordered by `sort_order`. |
| `images[].url` | string | Full image URL. |
| `images[].thumbnail` | string\|null | Thumbnail URL, if available. |
| `images[].alt` | string\|null | Alt text. |
| `images[].is_primary` | boolean | Whether this is the primary display image. |
| `parent` | object\|null | `{ id, name, slug }` of the grouped parent. Present on child products when `?with_siblings=1` is passed; `null` otherwise. Always `null` for standalone and grouped products. |
| `children` | array | For grouped products: always populated with child product objects (list shape). For child products with `?with_siblings=1`: populated with sibling products. Empty array in all other cases. |

**Not found response `404`:**
```json
{
  "message": "No query results for model [App\\Models\\Product] 999"
}
```

---

## Categories

### GET /api/categories

Returns all active top-level categories with their active children nested inside. Served from Redis cache.

**Auth required:** Yes

#### Response `200`

```json
{
  "data": [
    {
      "id": 3,
      "name": "Electronics",
      "slug": "electronics",
      "sort_order": 1,
      "image": "https://cdn.example.com/categories/electronics.jpg",
      "children": [
        {
          "id": 7,
          "name": "Headphones",
          "slug": "headphones",
          "image": "https://cdn.example.com/categories/headphones.jpg"
        },
        {
          "id": 8,
          "name": "Phones",
          "slug": "phones",
          "image": null
        }
      ]
    }
  ]
}
```

**Category object (list shape):**

| Field | Type | Description |
|---|---|---|
| `id` | integer | Category ID. |
| `name` | string | Category display name. |
| `slug` | string | URL-safe name. |
| `sort_order` | integer | Display order (ascending). |
| `image` | string\|null | Primary image URL. |
| `children` | array | Active direct children. Each child: `{ id, name, slug, image }`. Empty array if no children. |

---

### GET /api/categories/{id}

Returns full detail for a single active category, including its parent and children. Served from Redis cache.

**Auth required:** Yes

| Path Parameter | Type | Description |
|---|---|---|
| `id` | integer | Category ID. |

#### Response `200`

```json
{
  "id": 7,
  "name": "Headphones",
  "slug": "headphones",
  "sort_order": 1,
  "image": "https://cdn.example.com/categories/headphones.jpg",
  "children": [],
  "description": "All headphone types.",
  "parent": {
    "id": 3,
    "name": "Electronics",
    "slug": "electronics"
  }
}
```

**Additional fields (detail only):**

| Field | Type | Description |
|---|---|---|
| `description` | string\|null | Category description. |
| `parent` | object\|null | `{ id, name, slug }` of the parent category. `null` for top-level categories. |

---

## Brands

### GET /api/brands

Returns all active brands ordered alphabetically. Served from Redis cache.

**Auth required:** Yes

#### Response `200`

```json
{
  "data": [
    {
      "id": 2,
      "name": "Acme Corp",
      "slug": "acme-corp",
      "image": "https://cdn.example.com/brands/acme.jpg"
    }
  ]
}
```

| Field | Type | Description |
|---|---|---|
| `id` | integer | Brand ID. |
| `name` | string | Brand display name. |
| `slug` | string | URL-safe name. |
| `image` | string\|null | Primary brand image URL. |

---

### GET /api/brands/{id}

Returns detail for a single active brand. Served from Redis cache.

**Auth required:** Yes

| Path Parameter | Type | Description |
|---|---|---|
| `id` | integer | Brand ID. |

#### Response `200`

```json
{
  "id": 2,
  "name": "Acme Corp",
  "slug": "acme-corp",
  "description": "Quality products since 1952.",
  "image": "https://cdn.example.com/brands/acme.jpg"
}
```

| Field | Type | Description |
|---|---|---|
| `id` | integer | Brand ID. |
| `name` | string | Brand display name. |
| `slug` | string | URL-safe name. |
| `description` | string\|null | Brand description. |
| `image` | string\|null | Primary image URL. |

---

## Orders

### GET /api/orders

Returns the authenticated user's order history, paginated. Fixed at 15 orders per page, most recent first.

**Auth required:** Yes

#### Response `200`

```json
{
  "data": [
    {
      "id": 55,
      "invoice_no": "55",
      "status": "shipped",
      "payment_status": "paid",
      "sync_status": "synced",
      "currency": "USD",
      "total": "134.97",
      "created_at": "2026-03-15T10:22:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 4,
    "last_page": 1
  }
}
```

**Order object (list shape):**

| Field | Type | Description |
|---|---|---|
| `id` | integer | Gateway order ID. |
| `invoice_no` | string | Human-readable invoice number. |
| `status` | string | Customer-facing order status. See [Order Statuses](#order-statuses). |
| `payment_status` | string\|null | Payment status. See [Payment Statuses](#payment-statuses). |
| `sync_status` | string | ERP sync status. See [Sync Statuses](#sync-statuses). |
| `currency` | string | ISO 4217 currency code (e.g. `USD`). |
| `total` | numeric | Final order total. |
| `created_at` | string | ISO 8601 timestamp. |

---

### POST /api/orders

Places a new order. Prices are resolved from current product records at time of placement — the client does not send prices. Returns `201` immediately and dispatches the order to the ERP queue asynchronously.

**Auth required:** Yes

#### Request Body

```json
{
  "billing_first_name": "Jane",
  "billing_last_name": "Doe",
  "billing_company": "Acme Inc.",
  "billing_address_1": "123 Main Street",
  "billing_address_2": "Suite 4B",
  "billing_city": "Springfield",
  "billing_state": "IL",
  "billing_postcode": "62701",
  "billing_country": "US",
  "billing_email": "jane.doe@example.com",
  "billing_phone": "+1-555-0100",
  "shipping_first_name": "Jane",
  "shipping_last_name": "Doe",
  "shipping_company": null,
  "shipping_address_1": "123 Main Street",
  "shipping_address_2": null,
  "shipping_city": "Springfield",
  "shipping_state": "IL",
  "shipping_postcode": "62701",
  "shipping_country": "US",
  "currency": "USD",
  "discount_total": 0,
  "shipping_total": 9.99,
  "total_tax": 0,
  "customer_note": "Please leave at the door.",
  "meta_data": {},
  "items": [
    { "product_id": 12, "quantity": 2 },
    { "product_id": 7,  "quantity": 1 }
  ]
}
```

**Billing fields:**

| Field | Type | Required | Constraints | Description |
|---|---|---|---|---|
| `billing_first_name` | string | Yes | max 100 | |
| `billing_last_name` | string | Yes | max 100 | |
| `billing_company` | string\|null | No | | |
| `billing_address_1` | string | Yes | max 255 | |
| `billing_address_2` | string\|null | No | | |
| `billing_city` | string | Yes | max 100 | |
| `billing_state` | string\|null | No | | |
| `billing_postcode` | string | Yes | max 20 | |
| `billing_country` | string | Yes | exactly 2 chars | ISO 3166-1 alpha-2 country code. |
| `billing_email` | string | Yes | valid email | |
| `billing_phone` | string | Yes | max 20 | |

**Shipping fields:**

| Field | Type | Required | Constraints | Description |
|---|---|---|---|---|
| `shipping_first_name` | string | Yes | max 100 | |
| `shipping_last_name` | string | Yes | max 100 | |
| `shipping_company` | string\|null | No | | |
| `shipping_address_1` | string | Yes | max 255 | |
| `shipping_address_2` | string\|null | No | | |
| `shipping_city` | string | Yes | max 100 | |
| `shipping_state` | string\|null | No | | |
| `shipping_postcode` | string | Yes | max 20 | |
| `shipping_country` | string | Yes | exactly 2 chars | ISO 3166-1 alpha-2 country code. |

**Order-level fields:**

| Field | Type | Required | Constraints | Description |
|---|---|---|---|---|
| `items` | array | Yes | min 1 item | Line items. |
| `items[].product_id` | integer | Yes | must exist in `products` table | Gateway product ID. |
| `items[].quantity` | integer | Yes | min 1 | |
| `currency` | string | No | exactly 3 chars | ISO 4217 code. Defaults to `USD`. |
| `discount_total` | numeric | No | min 0 | Order-level discount. Defaults to `0`. |
| `shipping_total` | numeric | No | min 0 | Shipping charge. Defaults to `0`. |
| `total_tax` | numeric | No | min 0 | Tax total. Defaults to `0`. |
| `customer_note` | string\|null | No | | Delivery instructions or note. |
| `meta_data` | object | No | | Arbitrary metadata. |

#### Total Calculation

The server calculates all totals — never trust values from the client for pricing:

```
line_total = sum(product.current_price × quantity) for each item
total      = line_total - discount_total + shipping_total + total_tax
```

`current_price` is `sale_price` if set, otherwise `regular_price`.

#### Response `201`

```json
{
  "message": "Order placed successfully",
  "data": {
    "id": 55,
    "invoice_no": "55",
    "status": "pending",
    "payment_status": "due",
    "sync_status": "pending",
    "currency": "USD",
    "total": "134.97",
    "created_at": "2026-04-04T08:30:00+00:00"
  }
}
```

**Unavailable product response `422`:**
```json
{
  "message": "Product ID 12 is unavailable or out of stock"
}
```

**Server error response `500`:**
```json
{
  "message": "Failed to place order"
}
```

#### Notes

- Prices are resolved server-side at placement time. Do not send unit prices — they are ignored
- All items must be active (`is_active = true`) and in stock (`in_stock = true`). Any unavailable item in the list will reject the entire order
- The order is dispatched to the ERP queue immediately after `201` is returned. `sync_status` will transition from `pending` → `syncing` → `synced` asynchronously
- The initial `status` is always `pending`; the initial `payment_status` is always `due`

---

### GET /api/orders/{id}

Returns full detail for a single order belonging to the authenticated user, including billing/shipping addresses and line items.

**Auth required:** Yes

| Path Parameter | Type | Description |
|---|---|---|
| `id` | integer | Gateway order ID. Must belong to the authenticated user. |

#### Response `200`

```json
{
  "id": 55,
  "invoice_no": "55",
  "status": "shipped",
  "payment_status": "paid",
  "sync_status": "synced",
  "currency": "USD",
  "total": "134.97",
  "created_at": "2026-03-15T10:22:00+00:00",
  "order_key": "order_aBcDeFgHiJkLm",
  "transaction_date": "2026-03-15T10:22:00+00:00",
  "line_total": "124.98",
  "discount_total": "0.00",
  "shipping_total": "9.99",
  "total_tax": "0.00",
  "customer_note": "Please leave at the door.",
  "billing": {
    "name": "Jane Doe",
    "company": "Acme Inc.",
    "address_1": "123 Main Street",
    "address_2": "Suite 4B",
    "city": "Springfield",
    "state": "IL",
    "postcode": "62701",
    "country": "US",
    "email": "jane.doe@example.com",
    "phone": "+1-555-0100"
  },
  "shipping": {
    "name": "Jane Doe",
    "company": null,
    "address_1": "123 Main Street",
    "address_2": null,
    "city": "Springfield",
    "state": "IL",
    "postcode": "62701",
    "country": "US"
  },
  "items": [
    {
      "id": 101,
      "name": "Wireless Headphones X1",
      "sku": "WH-X1-BLK",
      "quantity": 2,
      "unit_price": "119.99",
      "unit_tax": "0.00",
      "total": "239.98"
    }
  ]
}
```

**Additional fields (detail only):**

| Field | Type | Description |
|---|---|---|
| `order_key` | string | Unique order key. |
| `transaction_date` | string\|null | ISO 8601 timestamp of the transaction. |
| `line_total` | numeric | Sum of all line items before adjustments. |
| `discount_total` | numeric | Order-level discount applied. |
| `shipping_total` | numeric | Shipping charge. |
| `total_tax` | numeric | Tax total. |
| `customer_note` | string\|null | Customer's delivery note. |
| `billing` | object | Full billing address. `name` is `first_name + last_name`. |
| `shipping` | object | Full shipping address. `name` is `first_name + last_name`. |
| `items` | array | Snapshot of line items at time of order. |
| `items[].id` | integer | Order item ID. |
| `items[].name` | string | Product name at time of order (snapshot). |
| `items[].sku` | string\|null | SKU at time of order (snapshot). |
| `items[].quantity` | integer | Quantity ordered. |
| `items[].unit_price` | numeric | Price per unit at time of order. |
| `items[].unit_tax` | numeric | Tax per unit. |
| `items[].total` | numeric | `(unit_price + unit_tax) × quantity`. |

---

## Addresses

A user's address book. Each address has structured fields matching the billing/shipping format used in orders. One address per user can be marked `is_default` — the mobile app can use this to pre-fill order forms.

**Auth required:** Yes (all endpoints)

### Address object

```json
{
  "id": 1,
  "label": "Home",
  "first_name": "Jane",
  "last_name": "Doe",
  "company": null,
  "address_1": "123 Main Street",
  "address_2": null,
  "city": "Springfield",
  "state": "IL",
  "postcode": "62701",
  "country": "US",
  "phone": "+1-555-0100",
  "is_default": true
}
```

| Field | Type | Description |
|---|---|---|
| `id` | integer | Address ID. |
| `label` | string\|null | User-assigned label (e.g. "Home", "Office"). |
| `first_name` | string | First name. |
| `last_name` | string | Last name. |
| `company` | string\|null | Company name. |
| `address_1` | string | Primary address line. |
| `address_2` | string\|null | Secondary address line. |
| `city` | string | City. |
| `state` | string\|null | State or region. |
| `postcode` | string | Postal/ZIP code. |
| `country` | string | ISO 3166-1 alpha-2 country code (e.g. `US`). |
| `phone` | string\|null | Contact phone number. |
| `is_default` | boolean | Whether this is the user's default address. Exactly one address per user can be `true`. |

---

### GET /api/addresses

Returns all saved addresses for the authenticated user. The default address is always listed first.

#### Response `200`

```json
{
  "data": [
    {
      "id": 1,
      "label": "Home",
      "first_name": "Jane",
      "last_name": "Doe",
      "company": null,
      "address_1": "123 Main Street",
      "address_2": null,
      "city": "Springfield",
      "state": "IL",
      "postcode": "62701",
      "country": "US",
      "phone": "+1-555-0100",
      "is_default": true
    }
  ]
}
```

---

### POST /api/addresses

Creates a new address for the authenticated user. Returns `201` with the created address.

#### Request Body

```json
{
  "label": "Home",
  "first_name": "Jane",
  "last_name": "Doe",
  "company": null,
  "address_1": "123 Main Street",
  "address_2": null,
  "city": "Springfield",
  "state": "IL",
  "postcode": "62701",
  "country": "US",
  "phone": "+1-555-0100",
  "is_default": true
}
```

| Field | Type | Required | Constraints | Description |
|---|---|---|---|---|
| `label` | string | No | max 50 | Optional user-assigned label. |
| `first_name` | string | Yes | max 100 | |
| `last_name` | string | Yes | max 100 | |
| `company` | string\|null | No | max 255 | |
| `address_1` | string | Yes | max 255 | |
| `address_2` | string\|null | No | max 255 | |
| `city` | string | Yes | max 100 | |
| `state` | string\|null | No | max 100 | |
| `postcode` | string | Yes | max 20 | |
| `country` | string | Yes | exactly 2 chars | ISO 3166-1 alpha-2. |
| `phone` | string\|null | No | max 20 | |
| `is_default` | boolean | No | | When `true`, all other addresses are unset as default. |

#### Notes

- If this is the user's **first address**, it is automatically set as the default regardless of the `is_default` field.
- If `is_default` is `true`, all other addresses for the user have their `is_default` cleared atomically.

#### Response `201`

```json
{
  "message": "Address created successfully",
  "data": { ... }
}
```

---

### GET /api/addresses/{id}

Returns a single address belonging to the authenticated user.

| Path Parameter | Type | Description |
|---|---|---|
| `id` | integer | Address ID. Must belong to the authenticated user. |

#### Response `200`

Address object (see shape above).

**Not found / wrong user response `404`:**
```json
{
  "message": "No query results for model [App\\Models\\Address] 99"
}
```

---

### PATCH /api/addresses/{id}

Updates fields on an existing address. All fields are optional — only include fields to change.

**Auth required:** Yes

| Path Parameter | Type | Description |
|---|---|---|
| `id` | integer | Address ID. Must belong to the authenticated user. |

#### Request Body

Same fields as [POST /api/addresses](#post-apiaddresses), all optional.

#### Notes

- If `is_default` is `true`, all other addresses for the user have their `is_default` cleared atomically.
- Omitting `is_default` (or sending `false`) does not change the current default state of other addresses.

#### Response `200`

```json
{
  "message": "Address updated successfully",
  "data": { ... }
}
```

**Not found / wrong user response `404`:**
```json
{
  "message": "No query results for model [App\\Models\\Address] 99"
}
```

---

### DELETE /api/addresses/{id}

Deletes an address belonging to the authenticated user.

| Path Parameter | Type | Description |
|---|---|---|
| `id` | integer | Address ID. Must belong to the authenticated user. |

#### Response `200`

```json
{
  "message": "Address deleted"
}
```

**Not found / wrong user response `404`:**
```json
{
  "message": "No query results for model [App\\Models\\Address] 99"
}
```

---

## Reference

### Order Statuses

| Value | Description |
|---|---|
| `pending` | Order placed, awaiting processing. |
| `processing` | Order confirmed, being prepared. |
| `shipped` | Order dispatched. |
| `delivered` | Order delivered to customer. |
| `cancelled` | Order cancelled. |

### Payment Statuses

| Value | Description |
|---|---|
| `due` | Payment not yet received. |
| `paid` | Payment confirmed. |
| `refunded` | Payment refunded. |

### Sync Statuses

Reflects whether the order has been successfully sent to the ERP. Read-only — managed by the queue worker.

| Value | Description |
|---|---|
| `pending` | Queued, not yet sent to ERP. |
| `syncing` | Currently being sent to ERP. |
| `synced` | Successfully received by ERP. |
| `failed` | All 3 delivery attempts failed. |
