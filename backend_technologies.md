# Backend Technologies, Languages, and Libraries

The backend of the Student Behavior Analysis system is structured using a microservices-like architecture, divided into the Core Application (API) and a Machine Learning (ML) Service. Both services are containerized and orchestrated using Docker.

Here is a detailed breakdown of the technologies, languages, and libraries used across the backend section:

## 1. Core Application (Main API)
The core application handles routing, database interactions, authentication, role-based access control, and general business logic.

- **Language:** **PHP** (Version 8.3+)
- **Framework:** **Laravel** (Version 13.x)
- **Key Libraries & Packages:**
  - **[Laravel Sanctum](https://laravel.com/docs/sanctum):** Used for robust API token authentication and Single Page Application (SPA) authentication.
  - **[Spatie Laravel Permission](https://spatie.be/docs/laravel-permission):** Used for Role-Based Access Control (RBAC) to manage users, roles, and permissions (e.g., Admin, Teacher, Advisor, Student).
  - **[Spatie Laravel Activitylog](https://spatie.be/docs/laravel-activitylog):** Used for logging user activities and system events to maintain an audit trail.
  - **[Dedoc Scramble](https://scramble.dedoc.co/):** Used for automatically generating OpenAPI (Swagger) documentation for the API endpoints without writing PHPDoc blocks.
  - **[Laravel Tinker](https://laravel.com/docs/tinker):** Used as an interactive REPL (Read-Eval-Print Loop) for interacting with the application via the command line.
  - **Testing & Dev Tools:** `phpunit/phpunit` (unit/feature testing), `fakerphp/faker` (generating fake data for testing/seeding), `laravel/pint` (code style formatting), and `mockery/mockery` (mocking objects in tests).

## 2. Machine Learning Service (`ml-service`)
This service is dedicated to running predictive models and natural language processing tasks independently from the core application.

- **Language:** **Python**
- **Framework:** **FastAPI** (Used for building high-performance, asynchronous REST APIs for the ML endpoints)
- **Server:** **Uvicorn** (ASGI web server implementation for Python used to serve the FastAPI application)
- **Key Libraries & Packages:**
  - **[Pydantic](https://docs.pydantic.dev/):** Used for data validation and settings management using Python type annotations.
  - **[Scikit-Learn](https://scikit-learn.org/):** Core library used for standard machine learning algorithms, model training, and evaluation.
  - **[XGBoost](https://xgboost.readthedocs.io/) & [LightGBM](https://lightgbm.readthedocs.io/):** Advanced gradient boosting libraries used for high-performance predictive modeling (e.g., predicting student behavior or performance).
  - **[Pandas](https://pandas.pydata.org/) & [NumPy](https://numpy.org/):** Used for data manipulation, cleaning, and numerical computations before feeding data to the models.
  - **[SHAP](https://shap.readthedocs.io/):** Used for Explainable AI (XAI) to interpret the output of the machine learning models and understand which features influenced a prediction.

## 3. Infrastructure, Databases, and DevOps
The system relies on robust infrastructure tools for data storage, caching, web serving, and containerization.

- **Database:** **PostgreSQL** (Version 15) - Used as the primary relational database to store users, students, behavioral incidents, and system configurations.
- **Cache & Queue:** **Redis** - Used as an in-memory data structure store for caching API responses, session management, and processing background jobs/queues.
- **Web Server:** **Nginx** - Used as a high-performance web server and reverse proxy to route incoming HTTP requests to the Laravel core application.
- **Containerization:** **Docker** & **Docker Compose** - Used to containerize the entire stack (Laravel App, FastAPI ML Service, PostgreSQL, Redis, and Nginx) to ensure consistent environments across development, testing, and production.
- **Asset Bundling (Frontend integration within Backend):** **Vite** and **TailwindCSS** are configured (`package.json`, `vite.config.js`) for compiling frontend assets that may be served directly by the backend views.
