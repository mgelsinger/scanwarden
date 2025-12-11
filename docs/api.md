# ScanWarden API Documentation

## Overview

The ScanWarden API provides endpoints for user authentication, unit management, team composition, and product scanning functionality. All API endpoints are prefixed with `/api`.

## Base URL

```
http://your-domain.com/api
```

## Authentication

The API uses Laravel Sanctum for token-based authentication. After registering or logging in, you'll receive a bearer token that must be included in all authenticated requests.

### Token Usage

Include the token in the Authorization header:

```
Authorization: Bearer {your-token-here}
```

### Token Abilities

Tokens are issued with specific abilities:
- `mobile` - Standard client access for mobile/web applications
- `internal` - Reserved for internal tools (future use)

Currently, all tokens issued through `/register` and `/login` receive the `mobile` ability.

## Rate Limiting

- **General API**: 60 requests per minute
- **Scan Endpoint**: 10 requests per minute

When rate limits are exceeded, you'll receive a `429 Too Many Requests` response.

---

## Authentication Endpoints

### Register

Create a new user account and receive an authentication token.

**Endpoint:** `POST /api/register`

**Authentication:** Not required

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:** `201 Created`
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-12-11T10:00:00.000000Z",
    "updated_at": "2025-12-11T10:00:00.000000Z"
  },
  "token": "1|abc123..."
}
```

---

### Login

Authenticate an existing user and receive a token.

**Endpoint:** `POST /api/login`

**Authentication:** Not required

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:** `200 OK`
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-12-11T10:00:00.000000Z",
    "updated_at": "2025-12-11T10:00:00.000000Z"
  },
  "token": "2|xyz789..."
}
```

**Error Response:** `422 Unprocessable Entity`
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The provided credentials are incorrect."
    ]
  }
}
```

---

### Logout

Revoke the current authentication token.

**Endpoint:** `POST /api/logout`

**Authentication:** Required

**Request Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "message": "Logged out successfully"
}
```

---

### Get Current User

Retrieve the authenticated user's information.

**Endpoint:** `GET /api/user`

**Authentication:** Required

**Request Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "rating": 1000,
  "created_at": "2025-12-11T10:00:00.000000Z",
  "updated_at": "2025-12-11T10:00:00.000000Z"
}
```

---

## Units Endpoints

### List Units

Retrieve a paginated list of the authenticated user's units.

**Endpoint:** `GET /api/units`

**Authentication:** Required

**Query Parameters:**
- `page` (optional) - Page number for pagination (default: 1)

**Request Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "name": "Tech Guardian",
      "rarity": "rare",
      "tier": 1,
      "hp": 100,
      "current_hp": 100,
      "max_hp": 100,
      "attack": 30,
      "defense": 20,
      "speed": 25,
      "sector": {
        "id": 1,
        "name": "Tech Sector",
        "color": "#3B82F6"
      }
    }
  ],
  "links": { /* pagination links */ },
  "meta": { /* pagination metadata */ }
}
```

---

### Get Unit Details

Retrieve detailed information about a specific unit.

**Endpoint:** `GET /api/units/{unit}`

**Authentication:** Required

**Request Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "name": "Tech Guardian",
    "rarity": "rare",
    "tier": 1,
    "hp": 100,
    "current_hp": 100,
    "max_hp": 100,
    "attack": 30,
    "defense": 20,
    "speed": 25,
    "sector": {
      "id": 1,
      "name": "Tech Sector",
      "color": "#3B82F6"
    }
  }
}
```

**Error Response:** `403 Forbidden`
```json
{
  "message": "Forbidden",
  "code": "forbidden"
}
```

---

## Teams Endpoints

### List Teams

Retrieve all teams belonging to the authenticated user.

**Endpoint:** `GET /api/teams`

**Authentication:** Required

**Request Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "name": "My Main Team",
      "units_count": 3,
      "units": [
        {
          "id": 1,
          "name": "Tech Guardian",
          "rarity": "rare"
        }
      ]
    }
  ]
}
```

---

### Create Team

Create a new team.

**Endpoint:** `POST /api/teams`

**Authentication:** Required

**Request Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "My New Team"
}
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 2,
    "name": "My New Team",
    "units_count": 0,
    "units": []
  }
}
```

---

### Get Team Details

Retrieve detailed information about a specific team.

**Endpoint:** `GET /api/teams/{team}`

**Authentication:** Required

**Request Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "name": "My Main Team",
    "units_count": 3,
    "units": [
      {
        "id": 1,
        "name": "Tech Guardian",
        "rarity": "rare",
        "tier": 1,
        "sector": {
          "id": 1,
          "name": "Tech Sector"
        }
      }
    ]
  }
}
```

**Error Response:** `403 Forbidden`
```json
{
  "message": "Forbidden",
  "code": "forbidden"
}
```

---

### Update Team

Update a team's name.

**Endpoint:** `PUT /api/teams/{team}`

**Authentication:** Required

**Request Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Updated Team Name"
}
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "name": "Updated Team Name",
    "units_count": 3,
    "units": [ /* ... */ ]
  }
}
```

**Error Response:** `403 Forbidden`
```json
{
  "message": "Forbidden",
  "code": "forbidden"
}
```

---

### Delete Team

Delete a team.

**Endpoint:** `DELETE /api/teams/{team}`

**Authentication:** Required

**Request Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "message": "Team deleted successfully"
}
```

**Error Response:** `403 Forbidden`
```json
{
  "message": "Forbidden",
  "code": "forbidden"
}
```

---

### Add Unit to Team

Add a unit to a team (max 5 units per team).

**Endpoint:** `POST /api/teams/{team}/units`

**Authentication:** Required

**Request Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "unit_id": 5
}
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "name": "My Main Team",
    "units_count": 4,
    "units": [ /* ... */ ]
  }
}
```

**Error Response:** `422 Unprocessable Entity` (Team Full)
```json
{
  "message": "Team is full (max 5 units)"
}
```

**Error Response:** `403 Forbidden` (Unit doesn't belong to user)
```json
{
  "message": "Forbidden",
  "code": "forbidden"
}
```

---

### Remove Unit from Team

Remove a unit from a team.

**Endpoint:** `DELETE /api/teams/{team}/units/{unit}`

**Authentication:** Required

**Request Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "name": "My Main Team",
    "units_count": 2,
    "units": [ /* ... */ ]
  }
}
```

---

## Scan Endpoints

### Perform Scan

Scan a product UPC code to gain sector energy and potentially summon units.

**Endpoint:** `POST /api/scan`

**Authentication:** Required

**Rate Limit:** 10 requests per minute

**Request Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "upc": "123456789012"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "data": {
    "scan_record": {
      "id": 42,
      "user_id": 1,
      "raw_upc": "123456789012",
      "sector_id": 1,
      "sector": {
        "id": 1,
        "name": "Tech Sector",
        "color": "#3B82F6"
      },
      "rewards": {
        "energy_gained": 15,
        "sector_name": "Tech Sector",
        "should_summon": true,
        "summoned_unit": {
          "id": 10,
          "name": "Circuit Breaker",
          "rarity": "uncommon"
        },
        "essence_rewards": [
          {
            "type": "generic",
            "amount": 5
          }
        ]
      },
      "created_at": "2025-12-11T10:00:00.000000Z"
    },
    "rewards": { /* same as above */ }
  }
}
```

**Error Response:** `422 Unprocessable Entity`
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "upc": [
      "The upc field is required."
    ]
  }
}
```

---

### List Scans

Retrieve a paginated history of the authenticated user's scans.

**Endpoint:** `GET /api/scans`

**Authentication:** Required

**Query Parameters:**
- `page` (optional) - Page number for pagination (default: 1)

**Request Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 42,
      "user_id": 1,
      "raw_upc": "123456789012",
      "sector": {
        "id": 1,
        "name": "Tech Sector",
        "color": "#3B82F6"
      },
      "rewards": { /* ... */ },
      "created_at": "2025-12-11T10:00:00.000000Z"
    }
  ],
  "links": { /* pagination links */ },
  "meta": { /* pagination metadata */ }
}
```

---

## Error Responses

All API errors follow a consistent format:

### 401 Unauthenticated
```json
{
  "message": "Unauthenticated.",
  "code": "unauthenticated"
}
```

### 403 Forbidden
```json
{
  "message": "Forbidden.",
  "code": "forbidden"
}
```

### 404 Not Found
```json
{
  "message": "Not Found.",
  "code": "not_found"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "Error message 1",
      "Error message 2"
    ]
  }
}
```

### 429 Too Many Requests
```json
{
  "message": "Too Many Requests.",
  "code": "too_many_requests"
}
```

---

## Testing

To test the API, you can use tools like:
- **Postman** - Import the endpoints and configure authentication
- **cURL** - Command-line HTTP requests
- **HTTPie** - Modern command-line HTTP client

### Example cURL Request

```bash
# Register
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# Login
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'

# Get Units (with token)
curl -X GET http://localhost/api/units \
  -H "Authorization: Bearer {your-token-here}"

# Scan Product
curl -X POST http://localhost/api/scan \
  -H "Authorization: Bearer {your-token-here}" \
  -H "Content-Type: application/json" \
  -d '{"upc":"123456789012"}'
```

---

## Security Considerations

1. **Always use HTTPS** in production to protect tokens and sensitive data
2. **Store tokens securely** - Never expose them in client-side code or logs
3. **Implement token rotation** - Tokens should be refreshed periodically
4. **Rate limiting** - Respect the rate limits to avoid being blocked
5. **Input validation** - All input is validated server-side

---

## Support

For API issues or questions, please file an issue in the GitHub repository.
