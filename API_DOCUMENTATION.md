# Client Hour Management API - Complete Documentation

## Overview

The Client Hour Management API is a ledger-based hour tracking system that provides wallet management, timers, invoices, package purchases, and user management.

**Base URL:** `/api/v1`
**API Documentation UI:** `/docs/api` (Available in development)
**Authentication:** Laravel Sanctum (Bearer token)

## Quick Links

- [Authentication](#authentication)
- [Pagination](#pagination)
- [Permissions & Roles](#permissions--roles-management)
- [User Management](#user-management)
- [Client Management](#client-management)
- [Wallet Management](#wallet-management)
- [Transaction Management](#transaction-management)
- [Timer Management](#timer-management)
- [Invoice Management](#invoice-management)
- [Error Responses](#error-responses)

---

## Authentication

All authenticated endpoints require a Bearer token in the `Authorization` header:

```http
Authorization: Bearer {your-token}
```

For complete authentication documentation including login, logout, and user info endpoints, see the [Authentication Endpoints](#authentication-endpoints) section below.

---

## Pagination

**All list endpoints use Laravel's standard pagination.**

### How It Works

List endpoints (index methods) automatically paginate results using Laravel's built-in pagination:

**Query Parameters:**
- `per_page` - Items per page (default: 15, max: 100)
- `page` - Page number (default: 1)

**Example Request:**
```http
GET /api/v1/clients?per_page=25&page=2
```

**Example Response:**
```json
{
  "data": [
    {
      "id": 26,
      "name": "Client Name",
      ...
    }
  ],
  "links": {
    "first": "http://api.test/api/v1/clients?page=1",
    "last": "http://api.test/api/v1/clients?page=10",
    "prev": "http://api.test/api/v1/clients?page=1",
    "next": "http://api.test/api/v1/clients?page=3"
  },
  "meta": {
    "current_page": 2,
    "from": 26,
    "last_page": 10,
    "path": "http://api.test/api/v1/clients",
    "per_page": 25,
    "to": 50,
    "total": 250
  }
}
```

### Paginated Endpoints

The following endpoints support pagination:

| Endpoint | Default per_page | Max per_page |
|----------|------------------|--------------|
| `GET /clients` | 15 | 100 |
| `GET /users` | 15 | 100 |
| `GET /wallets` | 15 | 100 |
| `GET /timers` | 15 | 100 |
| `GET /invoices` | 15 | 100 |

### Non-Paginated Endpoints

These endpoints return all results (cached for performance):

| Endpoint | Reason | Cache TTL |
|----------|--------|-----------|
| `GET /permissions` | Small dataset (~100 items) | 60s |
| `GET /roles` | Small dataset (~5-10 items) | 60s |

### Pagination Metadata

Each paginated response includes:

**`links` object:**
- `first` - URL to first page
- `last` - URL to last page
- `prev` - URL to previous page (null if on first page)
- `next` - URL to next page (null if on last page)

**`meta` object:**
- `current_page` - Current page number
- `from` - Starting item index
- `last_page` - Total number of pages
- `path` - Base URL for pagination
- `per_page` - Items per page
- `to` - Ending item index
- `total` - Total number of items

### Frontend Usage

**TypeScript Example:**
```typescript
interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
  };
}

// Fetch paginated clients
async function fetchClients(page = 1, perPage = 15) {
  const response = await api.get<PaginatedResponse<Client>>(
    `/clients?page=${page}&per_page=${perPage}`
  );

  return {
    items: response.data.data,
    pagination: response.data.meta,
    links: response.data.links,
  };
}

// React example with pagination
function ClientList() {
  const [page, setPage] = useState(1);
  const [clients, setClients] = useState<Client[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>();

  useEffect(() => {
    fetchClients(page, 25).then(({ items, pagination }) => {
      setClients(items);
      setMeta(pagination);
    });
  }, [page]);

  return (
    <div>
      <ClientTable data={clients} />
      <Pagination
        currentPage={meta?.current_page}
        totalPages={meta?.last_page}
        onPageChange={setPage}
      />
    </div>
  );
}
```

**Best Practices:**
1. ✅ Always specify `per_page` to avoid inconsistent page sizes
2. ✅ Respect the max limit (100 items per page)
3. ✅ Use `meta.total` to show total count to users
4. ✅ Check `links.next` to determine if there are more pages
5. ✅ Cache results on frontend to avoid redundant requests

---

# User, Role & Permission Management API

**Endpoints for managing users, roles and permissions**

This document extends the main API guidelines with detailed information about user management endpoints.

---

## Permissions Endpoint (Read-Only)

### GET `/api/v1/permissions`

**Purpose:** List all available permissions in the system.

**Auth Required:** ✅ Yes
**Permission Required:** `permission.view_any`

**Pagination:** ❌ No (returns all ~100 permissions, cached for performance)

**Response (200):**
```json
{
  "data": {
    "client": [
      {
        "id": 1,
        "name": "client.view_any",
        "guard_name": "web",
        "created_at": "2024-01-01T00:00:00.000000Z"
      },
      ...
    ],
    "wallet": [...],
    "timer": [...]
  },
  "meta": {
    "total": 87,
    "cached": true,
    "cache_ttl": 60
  }
}
```

**Features:**
- ✅ Cached for 60 seconds
- ✅ Grouped by resource
- ✅ Sorted alphabetically
- ❌ No pagination (returns all permissions)

**Usage:**
```typescript
// Fetch all permissions
const response = await api.get('/permissions');
const permissions = response.data.data;

// Access specific resource permissions
const clientPermissions = permissions.client;
const walletPermissions = permissions.wallet;
```

---

## Roles Endpoints

### GET `/api/v1/roles`

**Purpose:** List all roles with their assigned permissions.

**Auth Required:** ✅ Yes
**Permission Required:** `role.view_any`

**Pagination:** ❌ No (returns all ~5-10 roles, cached for performance)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "admin",
      "guard_name": "web",
      "permissions": [
        "client.create",
        "client.delete_any",
        "client.manage",
        ...
      ],
      "permissions_count": 87,
      "created_at": "2024-01-01T00:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "staff",
      "guard_name": "web",
      "permissions": [...],
      "permissions_count": 35,
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "total": 3,
    "cached": true,
    "cache_ttl": 60
  }
}
```

**Features:**
- ✅ Cached for 60 seconds
- ✅ Includes permission list for each role
- ✅ Permission count
- ❌ No pagination (returns all roles)

---

### GET `/api/v1/roles/{role}`

**Purpose:** Get details of a specific role.

**Auth Required:** ✅ Yes
**Permission Required:** `role.view_any`

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "admin",
    "guard_name": "web",
    "permissions": ["client.create", ...],
    "permissions_count": 87,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

### POST `/api/v1/roles`

**Purpose:** Create a new role.

**Auth Required:** ✅ Yes
**Permission Required:** `role.create`

**Request Body:**
```json
{
  "name": "manager",
  "permissions": [
    "client.view_any",
    "wallet.view_any",
    "timer.view_any"
  ]
}
```

**Validation:**
- `name` - required, string, max:255, unique
- `permissions` - optional, array
- `permissions.*` - string, must exist in permissions table

**Response (201):**
```json
{
  "message": "Role created successfully.",
  "data": {
    "id": 4,
    "name": "manager",
    "guard_name": "web",
    "permissions": [
      "client.view_any",
      "timer.view_any",
      "wallet.view_any"
    ],
    "permissions_count": 3
  }
}
```

**Side Effects:**
- ✅ Clears role list cache

---

### PUT/PATCH `/api/v1/roles/{role}`

**Purpose:** Update an existing role.

**Auth Required:** ✅ Yes
**Permission Required:** `role.update`

**Request Body:**
```json
{
  "name": "senior-manager",
  "permissions": [
    "client.view_any",
    "client.create",
    "wallet.view_any"
  ]
}
```

**Validation:**
- `name` - optional, string, max:255, unique (except current)
- `permissions` - optional, array
- `permissions.*` - string, must exist in permissions table

**Response (200):**
```json
{
  "message": "Role updated successfully.",
  "data": {
    "id": 4,
    "name": "senior-manager",
    "guard_name": "web",
    "permissions": [...],
    "permissions_count": 3
  }
}
```

**Side Effects:**
- ✅ Clears role list cache
- ✅ Syncs permissions (replaces old with new)

---

### DELETE `/api/v1/roles/{role}`

**Purpose:** Delete a role.

**Auth Required:** ✅ Yes
**Permission Required:** `role.delete`

**Response (200):**
```json
{
  "message": "Role deleted successfully."
}
```

**Error Response (422) - Core Role Protection:**
```json
{
  "message": "Core roles (admin, staff, client) cannot be deleted."
}
```

**Business Rules:**
- ❌ Cannot delete core roles (admin, staff, client)
- ✅ Users with this role will lose it
- ✅ Clears role list cache

---

## Users Endpoints

### GET `/api/v1/users`

**Purpose:** List all users.

**Auth Required:** ✅ Yes
**Permission Required:** `user.view_any`

**Pagination:** ✅ Yes (default: 15 per page, max: 100) - See [Pagination](#pagination) section

**Query Parameters:**
- `client_id` - Filter by client
- `role` - Filter by role name
- `active` - Filter by active status (boolean)
- `search` - Search by name or email
- `per_page` - Items per page (max 100, default 15)
- `page` - Page number (default 1)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "client_id": null,
      "active": true,
      "email_verified_at": "2024-01-01T00:00:00.000000Z",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z",
      "client": null,
      "roles": ["admin"],
      "permissions": ["client.create", ...]
    }
  ],
  "links": {...},
  "meta": {...}
}
```

**Usage Examples:**
```typescript
// Get all users
GET /api/v1/users

// Get users of specific client
GET /api/v1/users?client_id=5

// Get only admin users
GET /api/v1/users?role=admin

// Get inactive users
GET /api/v1/users?active=false

// Search users
GET /api/v1/users?search=john

// Combined filters
GET /api/v1/users?role=staff&active=true&per_page=25
```

---

### GET `/api/v1/users/{user}`

**Purpose:** Get details of a specific user.

**Auth Required:** ✅ Yes
**Permission Required:** `user.view_any`

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "client_id": null,
    "active": true,
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "client": null,
    "roles": ["admin"],
    "permissions": ["client.create", ...]
  }
}
```

---

### POST `/api/v1/users`

**Purpose:** Create a new user.

**Auth Required:** ✅ Yes
**Permission Required:** `user.create`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "client_id": 5,
  "active": true,
  "roles": ["staff"]
}
```

**Validation:**
- `name` - required, string, max:255
- `email` - required, email, max:255, unique
- `password` - required, min:8 (Laravel Password rules)
- `client_id` - optional, must exist in clients table
- `active` - optional, boolean (default: true)
- `roles` - optional, array of role names

**Response (201):**
```json
{
  "message": "User created successfully.",
  "data": {
    "id": 10,
    "name": "John Doe",
    "email": "john@example.com",
    "client_id": 5,
    "active": true,
    "roles": ["staff"],
    "permissions": [...]
  }
}
```

**Usage Examples:**
```typescript
// Create admin user (no client)
{
  "name": "Admin User",
  "email": "admin@example.com",
  "password": "SecurePass123!",
  "roles": ["admin"]
}

// Create client user (linked to client)
{
  "name": "Client User",
  "email": "client@example.com",
  "password": "SecurePass123!",
  "client_id": 5,
  "roles": ["client"]
}

// Create staff user
{
  "name": "Staff User",
  "email": "staff@example.com",
  "password": "SecurePass123!",
  "roles": ["staff"]
}
```

---

### PUT/PATCH `/api/v1/users/{user}`

**Purpose:** Update an existing user.

**Auth Required:** ✅ Yes
**Permission Required:** `user.update_any`

**Request Body (all fields optional):**
```json
{
  "name": "John Smith",
  "email": "johnsmith@example.com",
  "password": "NewPassword123!",
  "client_id": 6,
  "active": false,
  "roles": ["staff", "manager"]
}
```

**Validation:**
- `name` - optional, string, max:255
- `email` - optional, email, max:255, unique (except current)
- `password` - optional, min:8 (Laravel Password rules)
- `client_id` - optional, nullable, must exist in clients table
- `active` - optional, boolean
- `roles` - optional, array of role names (replaces old roles)

**Response (200):**
```json
{
  "message": "User updated successfully.",
  "data": {
    "id": 10,
    "name": "John Smith",
    "email": "johnsmith@example.com",
    "client_id": 6,
    "active": false,
    "roles": ["staff", "manager"],
    "permissions": [...]
  }
}
```

**Usage Examples:**
```typescript
// Change user email
PATCH /api/v1/users/10
{
  "email": "newemail@example.com"
}

// Deactivate user
PATCH /api/v1/users/10
{
  "active": false
}

// Remove client association
PATCH /api/v1/users/10
{
  "client_id": null
}

// Change roles
PATCH /api/v1/users/10
{
  "roles": ["admin"]
}
```

---

### DELETE `/api/v1/users/{user}`

**Purpose:** Delete a user.

**Auth Required:** ✅ Yes
**Permission Required:** `user.delete_any`

**Response (200):**
```json
{
  "message": "User deleted successfully."
}
```

**Error Response (422) - Self-Deletion Protection:**
```json
{
  "message": "You cannot delete your own account."
}
```

**Business Rules:**
- ❌ Cannot delete yourself
- ✅ Hard delete (not soft delete)
- ✅ Associated tokens are revoked

---

### POST `/api/v1/users/{user}/assign-roles`

**Purpose:** Assign roles to a user (convenience endpoint).

**Auth Required:** ✅ Yes
**Permission Required:** `user.update_any`

**Request Body:**
```json
{
  "roles": ["admin", "manager"]
}
```

**Validation:**
- `roles` - required, array, min:1
- `roles.*` - string, must exist in roles table

**Response (200):**
```json
{
  "message": "Roles assigned successfully.",
  "data": {
    "id": 10,
    "name": "John Doe",
    "roles": ["admin", "manager"],
    "permissions": ["client.create", ...]
  }
}
```

**Note:** This is equivalent to `PATCH /api/v1/users/{user}` with `roles` field, but returns only role/permission data.

---

## Caching Strategy

Both `/permissions` and `/roles` endpoints use caching to avoid database overhead:

**Cache Key:**
- Permissions: `api.permissions.list`
- Roles: `api.roles.list`

**TTL:** 60 seconds

**Cache Invalidation:**
- Creating a role → Clears `api.roles.list`
- Updating a role → Clears `api.roles.list`
- Deleting a role → Clears `api.roles.list`
- Permissions cache is never cleared (permissions are rarely added/removed)

**Manual Cache Clear:**
```bash
php artisan cache:forget api.permissions.list
php artisan cache:forget api.roles.list
```

---

## Frontend Integration Examples

### Fetch Permissions for Select Dropdown

```typescript
interface Permission {
  id: number;
  name: string;
  guard_name: string;
  created_at: string;
}

async function loadPermissions() {
  const response = await api.get('/permissions');
  const permissionsByResource = response.data.data;

  // Flatten for dropdown
  const allPermissions: Permission[] = Object.values(permissionsByResource)
    .flat();

  return allPermissions;
}

// Usage in React
function PermissionSelect() {
  const [permissions, setPermissions] = useState<Permission[]>([]);

  useEffect(() => {
    loadPermissions().then(setPermissions);
  }, []);

  return (
    <select multiple>
      {permissions.map(p => (
        <option key={p.id} value={p.name}>
          {p.name}
        </option>
      ))}
    </select>
  );
}
```

### Fetch Roles for Management UI

```typescript
interface Role {
  id: number;
  name: string;
  permissions: string[];
  permissions_count: number;
  created_at: string;
}

async function loadRoles() {
  const response = await api.get('/roles');
  return response.data.data as Role[];
}

// Usage in React
function RoleList() {
  const [roles, setRoles] = useState<Role[]>([]);

  useEffect(() => {
    loadRoles().then(setRoles);
  }, []);

  return (
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Permissions</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        {roles.map(role => (
          <tr key={role.id}>
            <td>{role.name}</td>
            <td>{role.permissions_count} permissions</td>
            <td>
              <button onClick={() => editRole(role)}>Edit</button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
```

### Create User with Role

```typescript
interface CreateUserRequest {
  name: string;
  email: string;
  password: string;
  client_id?: number | null;
  active?: boolean;
  roles?: string[];
}

async function createUser(data: CreateUserRequest) {
  const response = await api.post('/users', data);
  return response.data.data;
}

// Usage
const newUser = await createUser({
  name: "John Doe",
  email: "john@example.com",
  password: "SecurePass123!",
  roles: ["staff"]
});
```

### Update User Roles

```typescript
async function updateUserRoles(userId: number, roles: string[]) {
  const response = await api.post(`/users/${userId}/assign-roles`, {
    roles
  });
  return response.data.data;
}

// Usage
await updateUserRoles(10, ["admin", "manager"]);
```

---

## Best Practices

### 1. Cache Permission/Role Lists in Frontend

```typescript
// Store in app state (Redux, Zustand, etc)
const useAppStore = create((set) => ({
  permissions: [],
  roles: [],

  loadPermissions: async () => {
    const data = await api.get('/permissions').then(r => r.data.data);
    set({ permissions: data });
  },

  loadRoles: async () => {
    const data = await api.get('/roles').then(r => r.data.data);
    set({ roles: data });
  },
}));

// Load once on app init
useEffect(() => {
  loadPermissions();
  loadRoles();
}, []);
```

### 2. Role-Based UI Rendering

```typescript
function AdminPanel() {
  const { hasRole } = useAuth();

  if (!hasRole('admin')) {
    return <AccessDenied />;
  }

  return (
    <div>
      <UserManagement />
      <RoleManagement />
    </div>
  );
}
```

### 3. Permission-Based Feature Flags

```typescript
function FeatureButton() {
  const { hasPermission } = useAuth();

  if (!hasPermission('client.create')) {
    return null;
  }

  return <button>Create Client</button>;
}
```

---

## Security Notes

1. **All endpoints require authentication** via Sanctum token
2. **All endpoints check permissions** via Policies
3. **Core roles cannot be deleted** (admin, staff, client)
4. **Users cannot delete themselves**
5. **Password is hashed automatically** when creating/updating users
6. **Cache is invalidated** when roles are modified
7. **Permissions are read-only** (no create/update/delete endpoints)

---

## Summary

| Resource | Endpoints | Cached | CRUD |
|----------|-----------|--------|------|
| Permissions | 1 (index only) | ✅ 60s | Read-only |
| Roles | 5 (full CRUD) | ✅ 60s | Full CRUD |
| Users | 6 (full CRUD + assign-roles) | ❌ | Full CRUD |

**Total New Endpoints:** 12

All endpoints return consistent JSON responses with proper error handling and i18n messages.


---

## Authentication Endpoints

### POST `/auth/login`

Authenticate a user and receive an access token.

**Authentication:** None (public endpoint)

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "your-password"
}
```

**Validation:**
- `email`: required, email
- `password`: required, string

**Success Response (200):**
```json
{
  "token": "1|aB3dEf7gH9iJ0kL2mN4oP6qR8sT0uV2wX4yZ6",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "client_id": null,
    "active": true,
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "roles": ["admin"],
    "permissions": ["client.create", "wallet.view_any"],
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Error Responses:**
- **401** - Invalid credentials
- **403** - Inactive account
- **422** - Validation errors

---

### GET `/auth/info`

Retrieve authenticated user information including roles and permissions.

**Authentication:** Required

**Success Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "client_id": null,
    "active": true,
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "roles": ["admin"],
    "permissions": ["client.create", "wallet.view_any"],
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

### POST `/auth/logout`

Revoke the current access token.

**Authentication:** Required

**Success Response (200):**
```json
{
  "message": "Successfully logged out."
}
```

---

## Client Management

### GET `/clients`

List all clients.

**Authentication:** Required
**Permission:** `client.view_any`

**Pagination:** ✅ Yes (default: 15 per page, max: 100) - See [Pagination](#pagination) section

**Query Parameters:**
- `status` - Filter by status (active/inactive)
- `search` - Search by name or email
- `per_page` - Items per page (max 100, default 15)
- `page` - Page number (default 1)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Acme Corp",
      "email": "contact@acme.com",
      "phone": "+1234567890",
      "status": "active",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### GET `/clients/{client}`

Get details of a specific client.

**Authentication:** Required
**Permission:** `client.view` or `client.view_any`

**Query Parameters:**
- `include` - Load relationships (wallets)

**Success Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "Acme Corp",
    "email": "contact@acme.com",
    "phone": "+1234567890",
    "status": "active",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "wallets": [...]
  }
}
```

---

### POST `/clients`

Create a new client.

**Authentication:** Required
**Permission:** `client.create`

**Request Body:**
```json
{
  "name": "Acme Corp",
  "email": "contact@acme.com",
  "phone": "+1234567890",
  "status": "active"
}
```

**Validation:**
- `name`: required, string, max:255
- `email`: required, email, max:255, unique:clients
- `phone`: optional, string, max:50
- `status`: optional, in:active,inactive (default: active)

**Success Response (201):**
```json
{
  "message": "Client created successfully.",
  "data": {
    "id": 1,
    "name": "Acme Corp",
    "email": "contact@acme.com",
    "phone": "+1234567890",
    "status": "active",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

### PUT/PATCH `/clients/{client}`

Update an existing client.

**Authentication:** Required
**Permission:** `client.update_any` or `client.update_own`

**Request Body (all fields optional):**
```json
{
  "name": "Acme Corporation",
  "email": "info@acme.com",
  "phone": "+0987654321",
  "status": "inactive"
}
```

**Success Response (200):**
```json
{
  "message": "Client updated successfully.",
  "data": {
    "id": 1,
    "name": "Acme Corporation",
    "email": "info@acme.com",
    "phone": "+0987654321",
    "status": "inactive",
    "updated_at": "2024-01-10T10:00:00.000000Z"
  }
}
```

---

### DELETE `/clients/{client}`

Soft delete a client.

**Authentication:** Required
**Permission:** `client.delete_any`

**Success Response (200):**
```json
{
  "message": "Client deleted successfully."
}
```

---

## Wallet Management

### GET `/wallets`

List all wallets.

**Authentication:** Required
**Permission:** `wallet.view_any` or `wallet.view_own`

**Pagination:** ✅ Yes (default: 15 per page, max: 100) - See [Pagination](#pagination) section

**Query Parameters:**
- `client_id` - Filter by client
- `include_archived` - Include archived wallets (boolean)
- `per_page` - Items per page (max 100, default 15)
- `page` - Page number (default 1)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "client_id": 1,
      "name": "Main Wallet",
      "is_default": true,
      "archived_at": null,
      "balance_minutes": 1500,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z",
      "client": {
        "id": 1,
        "name": "Acme Corp",
        "email": "contact@acme.com"
      }
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### GET `/wallets/{wallet}`

Get details of a specific wallet including balance.

**Authentication:** Required
**Permission:** `wallet.view` or `wallet.view_any`

**Query Parameters:**
- `include` - Load relationships (client, transactions, packages)

**Success Response (200):**
```json
{
  "data": {
    "id": 1,
    "client_id": 1,
    "name": "Main Wallet",
    "is_default": true,
    "archived_at": null,
    "balance_minutes": 1500,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

### POST `/wallets`

Create a new wallet.

**Authentication:** Required
**Permission:** `wallet.create`

**Request Body:**
```json
{
  "client_id": 1,
  "name": "Project Wallet",
  "is_default": false
}
```

**Validation:**
- `client_id`: required, exists:clients,id
- `name`: required, string, max:255
- `is_default`: optional, boolean (default: false)

**Success Response (201):**
```json
{
  "message": "Wallet created successfully.",
  "data": {
    "id": 2,
    "client_id": 1,
    "name": "Project Wallet",
    "is_default": false,
    "archived_at": null,
    "balance_minutes": 0,
    "created_at": "2024-01-10T10:00:00.000000Z"
  }
}
```

---

### PUT/PATCH `/wallets/{wallet}`

Update an existing wallet.

**Authentication:** Required
**Permission:** `wallet.update_any` or `wallet.update_own`

**Request Body:**
```json
{
  "name": "Updated Wallet Name",
  "is_default": true
}
```

**Success Response (200):**
```json
{
  "message": "Wallet updated successfully.",
  "data": {
    "id": 2,
    "name": "Updated Wallet Name",
    "is_default": true,
    "updated_at": "2024-01-10T10:30:00.000000Z"
  }
}
```

---

### POST `/wallets/{wallet}/archive`

Archive a wallet.

**Authentication:** Required
**Permission:** `wallet.update_any`

**Success Response (200):**
```json
{
  "message": "Wallet archived successfully.",
  "data": {
    "id": 2,
    "archived_at": "2024-01-10T10:45:00.000000Z"
  }
}
```

**Business Rules:**
- Default wallet cannot be archived
- Archived wallets cannot accept new transactions

---

### POST `/wallets/{wallet}/unarchive`

Unarchive a wallet.

**Authentication:** Required
**Permission:** `wallet.update_any`

**Success Response (200):**
```json
{
  "message": "Wallet unarchived successfully.",
  "data": {
    "id": 2,
    "archived_at": null
  }
}
```

---

## Transaction Management

### POST `/transactions/credit`

Add credit (hours) to a wallet.

**Authentication:** Required
**Permission:** `transaction.create`

**Request Body:**
```json
{
  "wallet_id": 1,
  "minutes": 300,
  "description": "Monthly hours package",
  "internal_note": "Admin-only note",
  "occurred_at": "2024-01-10T10:00:00Z"
}
```

**Validation:**
- `wallet_id`: required, exists:wallets,id
- `minutes`: required, integer, min:1
- `description`: optional, string, max:500
- `internal_note`: optional, string, max:1000 (admin-only)
- `occurred_at`: optional, datetime (default: now)

**Success Response (201):**
```json
{
  "message": "Credit added successfully.",
  "data": {
    "id": 1,
    "wallet_id": 1,
    "type": "credit",
    "minutes": 300,
    "description": "Monthly hours package",
    "occurred_at": "2024-01-10T10:00:00.000000Z",
    "created_at": "2024-01-10T10:00:00.000000Z"
  }
}
```

---

### POST `/transactions/debit`

Add debit (remove hours) from a wallet.

**Authentication:** Required
**Permission:** `transaction.create`

**Request Body:**
```json
{
  "wallet_id": 1,
  "minutes": 150,
  "description": "Time tracked for project"
}
```

**Validation:**
- `wallet_id`: required, exists:wallets,id
- `minutes`: required, integer, min:1
- `description`: optional, string, max:500
- `internal_note`: optional, string, max:1000 (admin-only)
- `occurred_at`: optional, datetime (default: now)

**Success Response (201):**
```json
{
  "message": "Debit added successfully.",
  "data": {
    "id": 2,
    "wallet_id": 1,
    "type": "debit",
    "minutes": 150,
    "description": "Time tracked for project",
    "occurred_at": "2024-01-10T11:00:00.000000Z"
  }
}
```

**Business Rules:**
- Transactions are immutable (cannot be updated/deleted)
- Corrections require compensating transactions
- Negative balances are allowed (represent debt)

---

## Timer Management

### GET `/timers`

List timers.

**Authentication:** Required
**Permission:** `timer.view_any` or `timer.view_own`

**Pagination:** ✅ Yes (default: 15 per page, max: 100) - See [Pagination](#pagination) section

**Query Parameters:**
- `wallet_id` - Filter by wallet
- `status` - Filter by status (running, paused, stopped, cancelled)
- `per_page` - Items per page (max 100, default 15)
- `page` - Page number (default 1)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "wallet_id": 1,
      "started_by_id": 1,
      "status": "running",
      "description": "Working on feature X",
      "started_at": "2024-01-10T09:00:00.000000Z",
      "total_minutes": 120,
      "created_at": "2024-01-10T09:00:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### GET `/timers/{timer}`

Get details of a specific timer.

**Authentication:** Required
**Permission:** `timer.view` or `timer.view_any`

---

### POST `/timers`

Start a new timer.

**Authentication:** Required
**Permission:** `timer.create`

**Request Body:**
```json
{
  "wallet_id": 1,
  "description": "Working on feature X",
  "started_at": "2024-01-10T09:00:00Z"
}
```

**Validation:**
- `wallet_id`: required, exists:wallets,id
- `description`: optional, string, max:500
- `started_at`: optional, datetime (default: now)

**Success Response (201):**
```json
{
  "message": "Timer started.",
  "data": {
    "id": 1,
    "wallet_id": 1,
    "status": "running",
    "description": "Working on feature X",
    "started_at": "2024-01-10T09:00:00.000000Z"
  }
}
```

---

### POST `/timers/{timer}/pause`

Pause a running timer.

**Authentication:** Required
**Permission:** `timer.update_any` or `timer.update_own`

**Business Rules:** Timer must be in "running" state

---

### POST `/timers/{timer}/resume`

Resume a paused timer.

**Authentication:** Required
**Permission:** `timer.update_any` or `timer.update_own`

**Business Rules:** Timer must be in "paused" state

---

### POST `/timers/{timer}/stop`

Stop a timer (creates debit transaction).

**Authentication:** Required
**Permission:** `timer.update_any` or `timer.update_own`

**Success Response (200):**
```json
{
  "message": "Timer stopped.",
  "data": {
    "id": 1,
    "status": "stopped",
    "total_minutes": 120,
    "stopped_at": "2024-01-10T11:00:00.000000Z"
  }
}
```

**Business Rules:**
- Creates a debit transaction for the total time
- Cannot be undone

---

### POST `/timers/{timer}/cancel`

Cancel a timer (no ledger entry).

**Authentication:** Required
**Permission:** `timer.delete_any` or `timer.delete_own`

**Success Response (200):**
```json
{
  "message": "Timer cancelled."
}
```

**Business Rules:**
- Can only cancel before stopping
- Does NOT create ledger entry

---

## Invoice Management

### GET `/invoices`

List invoices.

**Authentication:** Required
**Permission:** `invoice.view_any` or `invoice.view_own`

**Pagination:** ✅ Yes (default: 15 per page, max: 100) - See [Pagination](#pagination) section

**Query Parameters:**
- `client_id` - Filter by client
- `status` - Filter by status (open, paid, cancelled)
- `per_page` - Items per page (max 100, default 15)
- `page` - Page number (default 1)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "client_id": 1,
      "hours": 10.5,
      "price_per_hour": 50.00,
      "total_amount": 525.00,
      "status": "open",
      "paid_at": null,
      "created_at": "2024-01-10T10:00:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {...}
}

---

### GET `/invoices/{invoice}`

Get details of a specific invoice.

**Authentication:** Required
**Permission:** `invoice.view` or `invoice.view_any`

---

### POST `/invoices`

Create an invoice.

**Authentication:** Required
**Permission:** `invoice.create`

**Request Body:**
```json
{
  "client_id": 1,
  "hours": 10.5,
  "price_per_hour": 50.00
}
```

**Validation:**
- `client_id`: required, exists:clients,id
- `hours`: required, numeric, min:0.01
- `price_per_hour`: required, numeric, min:0.01

**Success Response (201):**
```json
{
  "message": "Invoice created successfully.",
  "data": {
    "id": 1,
    "client_id": 1,
    "hours": 10.5,
    "price_per_hour": 50.00,
    "total_amount": 525.00,
    "status": "open"
  }
}
```

---

### POST `/invoices/{invoice}/mark-as-paid`

Mark an invoice as paid.

**Authentication:** Required
**Permission:** `invoice.mark_paid`

**Business Rules:**
- Invoice must be in "open" status
- Cannot be undone

---

### POST `/invoices/{invoice}/cancel`

Cancel an open invoice.

**Authentication:** Required
**Permission:** `invoice.update_any`

**Business Rules:**
- Invoice must be in "open" status
- Paid invoices cannot be cancelled

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "Resource not found."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Best Practices

1. **Ledger-First Design** - All balance modifications through transactions
2. **Permission-Based Authorization** - Check permissions, not roles
3. **Immutable Transactions** - Never update/delete, use compensating transactions
4. **Cache Strategy** - Permissions/Roles cached 60s
5. **Error Handling** - Always check for validation errors (422)
6. **Date Handling** - All dates in ISO 8601 format (UTC)
7. **Pagination** - Always paginate large result sets (max 100 per page)

---

## Summary

**Total Endpoints:** 43

| Resource | Endpoints |
|----------|-----------|
| Authentication | 3 |
| Permissions | 1 |
| Roles | 5 |
| Users | 6 |
| Clients | 5 |
| Wallets | 7 |
| Transactions | 2 |
| Timers | 7 |
| Invoices | 5 |
| Packages | 1 |
| Webhooks | 1 |

---

For detailed frontend integration examples and TypeScript types, see `frontend/USER-MANAGEMENT-API.md`.
