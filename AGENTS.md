# agents.md

## Project Overview

This project is a small web application designed to help users manage their diet. It consists of an HTML file, CSS for styling, and JavaScript files for application logic, data handling, and storage. The goal is to provide a simple, maintainable, and scalable frontend application.

## Development Guidelines

- **Architecture:**
  - Use Object-Oriented Programming (OOP) principles.
  - Follow the Model-View-Controller (MVC) pattern for code organization.
  - Apply all recognized best practices in frontend development.

- **Code Quality:**
  - Avoid files and classes with more than 400 lines of code.
  - Ensure all documentation, comments, and variable names are written in English.
  - Maintain clear, concise, and consistent naming conventions.
  - Write modular, reusable, and testable code.
  - Use version control (e.g., Git) for all changes.

- **Documentation:**
  - Provide comprehensive documentation for all modules, classes, and functions.
  - Keep documentation up-to-date with code changes.


- **Frontend Best Practices:**
  - Separate concerns: HTML for structure, CSS for styling, JS for logic.
  - Use semantic HTML elements.
  - Optimize performance and accessibility.
  - Ensure cross-browser compatibility.
  - Use modern ES6+ JavaScript features.
  - Keep UI responsive and user-friendly.

## Improvement Suggestions

- Refactor code to follow OOP and MVC patterns.
- Split large files/classes into smaller, manageable modules.
- Improve documentation and code comments.
- Enhance project structure for scalability.
- Regularly review and update best practices.

---


## NOTA IMPORTANTE SOBRE SYMFONY Y DOCKER

Todos los comandos relacionados con Symfony (por ejemplo, creación de entidades, migraciones, instalación de bundles, etc.) deben ejecutarse desde la consola del contenedor PHP usando docker-compose. Ejemplo:

```
docker-compose exec php bash
# Luego dentro del contenedor:
php bin/console <comando>
```

Esto asegura que las dependencias y el entorno sean los correctos para la aplicación Symfony ubicada en `/api/app`.

## Continuous Improvement Methodology


For every new functionality enhancement, agents must:
- Define a clear action plan in English (or Spanish if required).
- Document the plan in the `docs/` folder (e.g., `action-plan.md`, `plan-api-symfony.md`) and in the `plans/` folder if applicable.
- Reference all action plans and this methodology in `AGENTS.md`.
- Follow modular, reusable, and testable code practices.
- Include QA guidelines for each improvement.


### Action Plans History

- **action-plan-json-exception-listener.md**: Plan para configurar Symfony para devolver errores en formato JSON en peticiones API. Documentado en `docs/action-plan-json-exception-listener.md`.
- **plan-api-symfony.md**: Plan para crear una API ligera en PHP (Symfony) bajo `/api`, con endpoints para autenticación, gestión de usuarios y comidas, usando JWT y estructura mínima. Documentado en `docs/plan-api-symfony.md` y `plans/plan-api-symfony.md`.
- **action-plan-openai-shopping-list.md**: Plan para integrar OpenAI y generar una lista de la compra dinámica basada en el menú del usuario y el rango de fechas. Documentado en `docs/action-plan-openai-shopping-list.md` y endpoint en `docs/endpoint-shopping-list.md`.

See `docs/action-plan.md`, `docs/plan-api-symfony.md`, and `docs/action-plan-openai-shopping-list.md` for the current standard action plans for refactoring, UI improvements, and API backend.

---

This document serves as a guide for AI agents and developers to understand, maintain, and improve the project efficiently.