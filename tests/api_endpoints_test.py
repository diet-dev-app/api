import requests

BASE_URL = "http://localhost:8080/api"

# 1. Register a new user
def test_register():
    resp = requests.post(f"{BASE_URL}/register", json={
        "email": "testuser@example.com",
        "password": "testpass123",
        "name": "Test User"
    })
    print("Register:", resp.status_code, resp.json())

# 2. Login and get JWT token
def test_login():
    resp = requests.post(f"{BASE_URL}/login", json={
        "email": "testuser@example.com",
        "password": "testpass123"
    })
    print("Login:", resp.status_code, resp.json())
    return resp.json().get("token") or resp.json().get("id_token")

# 3. Get user profile
def test_profile(token):
    resp = requests.get(f"{BASE_URL}/user", headers={"Authorization": f"Bearer {token}"})
    print("Profile:", resp.status_code, resp.json())

# 4. CRUD for meals
def test_meals(token):
    headers = {"Authorization": f"Bearer {token}"}
    # Create
    resp = requests.post(f"{BASE_URL}/meals", json={
        "name": "Breakfast",
        "calories": 350,
        "date": "2026-02-15T08:00:00Z",
        "notes": "Oatmeal and fruit"
    }, headers=headers)
    print("Create Meal:", resp.status_code, resp.json())
    meal_id = resp.json().get("id")
    # List
    resp = requests.get(f"{BASE_URL}/meals", headers=headers)
    print("List Meals:", resp.status_code, resp.json())
    # Update
    resp = requests.put(f"{BASE_URL}/meals/{meal_id}", json={"calories": 400}, headers=headers)
    print("Update Meal:", resp.status_code, resp.json())
    # Delete
    resp = requests.delete(f"{BASE_URL}/meals/{meal_id}", headers=headers)
    print("Delete Meal:", resp.status_code, resp.json())

# 5. CRUD for meal options
def test_meal_options(token):
    headers = {"Authorization": f"Bearer {token}"}
    # Create
    resp = requests.post(f"{BASE_URL}/meal-options", json={
        "name": "Vegan Lunch",
        "description": "Salad and tofu"
    }, headers=headers)
    print("Create Meal Option:", resp.status_code, resp.json())
    option_id = resp.json().get("id")
    # List
    resp = requests.get(f"{BASE_URL}/meal-options", headers=headers)
    print("List Meal Options:", resp.status_code, resp.json())
    # Update
    resp = requests.put(f"{BASE_URL}/meal-options/{option_id}", json={"description": "Salad, tofu, and rice"}, headers=headers)
    print("Update Meal Option:", resp.status_code, resp.json())
    # Delete
    resp = requests.delete(f"{BASE_URL}/meal-options/{option_id}", headers=headers)
    print("Delete Meal Option:", resp.status_code, resp.json())

# 6. Get shopping list for a date range
def test_shopping_list(token):
    headers = {"Authorization": f"Bearer {token}"}
    params = {"start": "2026-02-15", "end": "2026-02-21"}
    resp = requests.get(f"{BASE_URL}/shopping-list", headers=headers, params=params)
    print("Shopping List:", resp.status_code, resp.json())

if __name__ == "__main__":
    test_register()
    token = test_login()
    if token:
        test_profile(token)
        test_meals(token)
        test_meal_options(token)
        test_shopping_list(token)
    else:
        print("Login failed, cannot test protected endpoints.")
