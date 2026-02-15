# action-plan-json-exception-listener.md

## Objective
Configure Symfony to return JSON-formatted error responses for API requests (when `Content-Type` or `Accept` is `application/json`), instead of rendering HTML error pages. This allows frontend and API clients to handle errors in a structured way.

## Steps
1. Create a `JsonExceptionListener` in `src/EventListener/` that listens to `kernel.exception`.
2. In the listener, check if the request expects JSON (by headers).
3. If so, set a `JsonResponse` with error details and status code.
4. Ensure the listener is autowired/autoconfigured in `services.yaml` (default in this project).
5. Document the change in `AGENTS.md` and this plan.

## QA Guidelines
- Test API endpoints with invalid data and verify error responses are JSON.
- Ensure HTML error pages are still rendered for non-API (non-JSON) requests.
- Validate error structure: `{ "error": { "message": ..., "code": ... } }`

## References
- Symfony Docs: https://symfony.com/doc/current/event_dispatcher.html
- Example implementation: https://symfony.com/doc/current/event_dispatcher.html#creating-an-event-listener

---

**Added by agent on 2026-02-15**
