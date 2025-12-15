---
applyTo: '**'
---

# Laravel Guidelines for this project

- This projects uses Laravel's 12.x version.
- It is created using Laravel's Livewire Starter Kit.
- Ensure you use laravel specific features wherever possible including but not limited to Form Request, Events, Resource Controllers, Resource Routes and etc.
- Follow the existing code style and conventions used in the project.
- Make sure to write clean, maintainable, and well-documented code.
- Use Eloquent ORM for database interactions and follow best practices for querying and relationships.
- Ensure proper validation and error handling in all user inputs and actions.
- Before writing relationships between models, check existing models for any similar relationships to maintain consistency.
- Before writing common functionalities, check for existing traits or helper functions that can be reused.
- Use Service classes for complex business logic to keep controllers thin. located in app/Services directory. Whenever possible move complex logic to service classes. If you find any existing logic in controller that can be moved to service class, refactor it.

# Livewire & AlpineJS Guidelines

- This project uses Livewire 3 with AlpineJS for building dynamic, interactive user interfaces.
- Follow Livewire's best practices for component structure, state management, and event handling.
- Use AlpineJS for simple interactivity and DOM manipulation within Livewire components.
- Ensure Livewire components are optimized for performance, minimizing unnecessary re-renders and data fetching.
- Use Livewire's built-in validation features for form inputs and user interactions.
- Follow the existing component structure and naming conventions used in the project.
- Ensure proper communication between Livewire components using events and properties.
- Write clean, maintainable, and well-documented code for Livewire components and AlpineJS scripts.