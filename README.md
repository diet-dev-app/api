# Diet API

## Overview
This project is a lightweight RESTful API built with Symfony, designed to help users manage their diet. It provides endpoints for authentication, user management, and CRUD operations for meals and meal options. The API is containerized using Docker and uses MySQL as its database.

## Features
- User authentication with JWT
- User registration and management
- Meal and meal option management (CRUD)
- Follows best practices: OOP, MVC, modular code
- Ready for local development with Docker Compose

## Target Audience
- Developers building diet or nutrition tracking apps
- Teams needing a backend for meal planning or health-related applications
- Anyone seeking a simple, extensible API for diet management

## Project Structure
- `src/Controller/`: Symfony controllers for API endpoints
- `src/Entity/`: Doctrine entities for database models
- `src/Repository/`: Data access layer
- `config/`: Symfony and service configuration
- `docker/`: Docker and Nginx configuration
- `public/`: Entry point for the API

## Getting Started

### Prerequisites
- Docker & Docker Compose

### Setup
1. Clone the repository
2. Run `docker-compose up -d` to start all services
3. Access the API at `http://localhost:8080`

### Usage
- Use tools like Postman or curl to interact with the API endpoints (see controller files for available routes)
- Default MySQL credentials are set in `compose.yaml` (change as needed)

## Development Guidelines
- Follow OOP and MVC patterns
- Keep code modular and well-documented
- See `AGENTS.md` for improvement methodology and action plans

## License
MIT (or as specified in the repository)
