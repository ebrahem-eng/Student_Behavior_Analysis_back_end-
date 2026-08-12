# Backend Implementation Plan
## Smart Platform for Student Behavior Analysis & Academic Dropout Prediction (Schools & Universities)

## 1. Overview

This project has **six actors**: System Admin, Teacher/Faculty, Academic Advisor/Program Coordinator, Student, Parent/Guardian, and a fully automated **System/AI Engine**. The AI Engine's requirements (risk scoring, ML-based prediction, sentiment analysis, pattern discovery, explainable AI, bias auditing, automatic retraining) are best served by dedicated data-science libraries that don't exist in the PHP ecosystem. The recommended architecture is therefore **polyglot**:

- **Laravel** — the system of record. Owns auth, multi-tenancy, academic data, business rules, notifications, the public API, and orchestration.
- **Python microservice** — a stateless ML/AI service (FastAPI) that Laravel calls internally over REST/gRPC for predictions, NLP, and explainability. This is where Python is required.

```
React (SPA/PWA)  <-->  Laravel API (PHP)  <-->  Python ML Service (FastAPI)
                              |                        |
                          MySQL/Postgres          Model store (S3/MLflow)
                              |
                        Redis (cache/queue/broadcast)
```

Laravel never re-implements ML logic; it sends feature data to the Python service and stores/relays the returned scores, labels, and explanations.

## 2. Tech Stack

| Concern | Choice |
|---|---|
| Framework | Laravel 11 (PHP 8.3) |
| Auth | Laravel Sanctum (SPA token auth) + Spatie `laravel-permission` for RBAC (5 roles) |
| Database | PostgreSQL (or MySQL 8) |
| Cache / Queue / Broadcast | Redis + Laravel Horizon |
| Real-time | Laravel Reverb or Pusher (WebSockets) for live alerts |
| Audit logging | Spatie `laravel-activitylog` |
| Backups | Spatie `laravel-backup` |
| API docs | Scramble or L5-Swagger (OpenAPI) — needed for the external researcher API |
| File exports | `barryvdh/laravel-dompdf` (PDF), `maatwebsite/excel` (Excel) |
| **ML / AI service (Python)** | FastAPI, scikit-learn / XGBoost / LightGBM, SHAP (explainability), spaCy or a transformer sentiment model (NLP), pandas/numpy, MLflow (model versioning + drift tracking) |
| ML service task runner | Celery + Redis, or FastAPI `BackgroundTasks` for retraining jobs |
| Monitoring | Prometheus + Grafana (explicit requirement) |
| Containerization | Docker Compose (php-fpm, nginx, python-ml, redis, postgres, worker, scheduler) |
| CI/CD | GitHub Actions |

## 3. Phased Plan

---

### Phase 0 — Foundations & Architecture
**Goal:** a working skeleton before any feature work.
- Set up Laravel project, Docker Compose (app, db, redis, nginx, python-ml container).
- Set up the Python FastAPI service skeleton with its own Dockerfile, health-check endpoint, and internal-only network exposure (never public).
- Define a shared internal auth scheme between Laravel and the Python service (signed service token / mTLS).
- Establish coding standards, `.env` structure, CI pipeline (lint, test, build) for both codebases.
- Design the core ER diagram: institutions, users, roles, academic terms, courses/sections, enrollments, grades, attendance, behavior logs, risk scores, alerts, recommendations, interventions, consents, audit logs.

**Deliverables:** running skeleton, CI green, ERD approved.

---

### Phase 1 — Identity, Roles & Multi-Institution Management
**Covers:** Admin #1, #2, #11, #12

- User model with 5 role types (Admin, Teacher, Advisor, Student, Parent) via Spatie permissions; fine-grained permission matrix per role.
- Institution model supporting both **school mode** (grades → classes → sections) and **university mode** (colleges → departments → programs).
- Configurable academic term structure per institution (2 semesters for schools, 2–3 terms for universities) — Admin #11.
- Extra university-only roles (program coordinator, college advisor) toggled when "university mode" is enabled — Admin #12.
- Admin CRUD screens (API endpoints) for institutions, structural units, and user provisioning (bulk import via CSV/Excel is recommended).

**Deliverables:** multi-tenant institution + role scaffolding, seeders, feature tests.

---

### Phase 2 — Academic Structure, Enrollment & Course Registration
**Covers:** Advisor #8, Student #8, Student #9, Advisor #7

- Course/section models, term-based scheduling.
- Student enrollment/registration API (self-service for students, oversight/edit for advisors) — Student #8, Advisor #8.
- Degree/graduation-plan model with progress calculation (% of requirements completed) — Student #9, Advisor #7.

**Deliverables:** registration API, graduation-progress endpoint.

---

### Phase 3 — Academic Data Ingestion (Grades, Attendance, Behavior)
**Covers:** Teacher #1, #2, #8; AI #12

- Endpoints for teachers to record grades, assignment/exam results, attendance, lateness, and class-participation — Teacher #1, #2.
- Per-course performance views (a university student may be enrolled in several courses) — Teacher #8.
- **LMS/External integration layer**: connectors for Moodle / Google Classroom to auto-pull grades & attendance via API — AI #12. Build this as an adapter interface so more LMS providers can be added later.
- Data validation & normalization pipeline so ingested data is analysis-ready (this is the feature set later sent to the Python service).

**Deliverables:** data-entry API, LMS sync jobs (queued), validation rules.

---

### Phase 4 — Risk Engine Core (Thresholds & Tiered Alerts)
**Covers:** Admin #3, #4, #5; Teacher #4; Advisor #1, #2; Student #2; Parent #1

- Configurable-threshold engine: institutions/admins set the risk % that triggers an alert instead of a hardcoded value (e.g. 80%) — Admin #4.
- Three-tier alert severity (low/medium/high) instead of a single flag — Admin #5.
- Alert generation service that consumes the risk score returned by the Python ML service (Phase 5) and fans it out to the right recipients (teacher, advisor, student, parent) — Teacher #4, Advisor #2, Student #2, Parent #1.
- Admin dashboard data endpoints: count of at-risk students, top problem subjects, pass/absence rates — Admin #3.
- Sortable "at-risk students" list endpoint for advisors, ordered by risk score — Advisor #1.

**Deliverables:** threshold config API, alert engine, admin stats endpoints.

---

### Phase 5 — Python ML Microservice: Prediction, Explainability & Fairness
**Covers:** AI Engine #1–#3, #6–#9 (Python required for all of these)

This phase is executed largely in the **Python/FastAPI service**, with Laravel acting as the client.

- **Feature/indicator computation** (commitment index, performance index, aggregate risk index) from raw academic + behavioral data — AI #1. (Can live in Laravel as a data-prep step, or in Python; recommend computing simple aggregates in Laravel and complex/derived features in Python for consistency with the model.)
- **Prediction model**: classify each student as at-risk / stable / high-performing using a trained ML model (start with gradient boosting — XGBoost/LightGBM — or logistic regression as a baseline) — AI #2.
- **Rule + ML hybrid recommendation generation**: map detected risk causes (e.g. attendance drop, grade decline) to suggested interventions — AI #3.
- **Explainable AI (XAI)**: SHAP values to show how much each factor (absence, exam scores, participation) contributed to a student's risk score — AI #6.
- **Periodic retraining pipeline**: scheduled job (Celery beat / cron) that retrains on new data, tracks accuracy drift, and version-stamps models via MLflow — AI #7.
- **Bias & fairness testing**: periodic checks that the model doesn't systematically disadvantage a subgroup (e.g. by gender, institution, program) using fairness metrics (e.g. demographic parity, equalized odds) — AI #8.
- Internal REST endpoints: `POST /predict`, `POST /explain`, `POST /retrain`, `GET /model/health` — consumed only by Laravel, never exposed publicly.
- Laravel-side: a `PredictionService` class that calls the Python service, persists risk scores/explanations, and triggers the Phase 4 alert engine.

**Deliverables:** trained baseline model, FastAPI prediction/explainability endpoints, retraining job, fairness test suite, Laravel integration client.

---

### Phase 6 — Recommendations, Intervention Workflow & Effectiveness Tracking
**Covers:** Teacher #5, #6; Advisor #3

- Advisor-facing endpoint to review/edit/approve AI-generated recommendations before they're sent — Advisor #3.
- Teacher-facing endpoint to mark a recommendation as "implemented" and log the intervention — Teacher #5.
- Follow-up tracking: record whether the student's performance improved after the intervention, to later measure effectiveness — Teacher #6.
- Feed this outcome data back as a labeled dataset for future model evaluation (Phase 5/9).

**Deliverables:** recommendation lifecycle API (proposed → edited → sent → implemented → outcome logged).

---

### Phase 7 — NLP: Sentiment Analysis, Chatbot & Pattern Discovery
**Covers:** AI #4, #5; Advisor #5, #6; Student #4, #6

- **Survey/sentiment analysis pipeline** (Python/NLP): process student well-being survey text to gauge satisfaction/stress level — AI #4, Student #6.
- **Pattern discovery**: mine historical data for recurring relationships (e.g. absence correlated with failure) to support proactive planning — AI #5, Advisor #6.
- **Academic chatbot backend**: Laravel endpoint that accepts a natural-language question ("Why did this student's performance drop?"), forwards it (with the student's structured data + SHAP explanation) to a Python NLP/LLM layer, and returns a grounded answer — Advisor #5, Student #4.
- Chatbot must be scoped by role/permission — an advisor can query any assigned student; a student can only query their own data.

**Deliverables:** sentiment-analysis endpoint, pattern-mining job, chatbot Q&A endpoint.

---

### Phase 8 — Future Performance Projection & Retrospective Accuracy
**Covers:** AI #6 (projection), Student #5, Advisor #9

- Endpoint that projects a student's likely end-of-term GPA/grade if current trends continue — Student #5.
- Retrospective evaluation job: after term end, compare predictions vs. actual outcomes and expose accuracy metrics to advisors/admins — Advisor #9.

**Deliverables:** projection endpoint, accuracy-report job & API.

---

### Phase 9 — Notifications, Communication Portal & Consent
**Covers:** Parent #1, #3, #4, #5, #6; Advisor #4; Student #7; AI #13

- Multi-channel notification dispatcher (email, SMS, push, in-app) with per-user channel/frequency preferences — Parent #5; AI #13.
- Push notifications via mobile app or PWA for parents and students — Parent #6.
- Documented communication portal: threaded messages tied to a specific student's record, between advisor and parent/student — Advisor #4, Parent #3.
- Consent management: explicit student consent, or parental consent for minors, required before data analysis runs — Student #7, Parent #4. Enforce this as a hard gate before Phase 5 predictions are generated for a given student.

**Deliverables:** notification dispatch service (queued jobs), messaging API, consent-gate middleware.

---

### Phase 10 — Reporting, Exports & Dashboards
**Covers:** Teacher #7, Admin #3, Parent #2

- Generate class/section/course performance reports as PDF or Excel — Teacher #7.
- Periodic (monthly) parent-facing student report generation — Parent #2.
- Aggregate dashboard endpoints feeding the React admin dashboard (Phase covered already in Phase 4, extended here with export).

**Deliverables:** report generation jobs, PDF/Excel export endpoints.

---

### Phase 11 — Security, Privacy & Compliance
**Covers:** Admin #6, #7; AI #10, #11; General #4

- Full audit trail: who accessed which student's data, when, and why — Admin #6, AI #10 (auto-logged, no manual step required).
- Data-protection policy engine: toggle FERPA-style compliance rules, field-level encryption, and anonymization for aggregate analytics — Admin #7, AI #11.
- Encrypt data at rest (DB column encryption for sensitive fields) and in transit (HTTPS everywhere) — General #4.
- Rate limiting, brute-force protection, and role-based field-level authorization policies (Laravel Policies/Gates) on every sensitive endpoint.

**Deliverables:** audit log middleware, encryption-at-rest for PII, compliance settings panel API.

---

### Phase 12 — Public API for Researchers/External Systems
**Covers:** Admin #10

- API-key issuance and scope management for external researchers/systems — Admin #10.
- Versioned, documented (OpenAPI) public API with strict rate limiting and anonymized/aggregated data only by default.

**Deliverables:** API-key management panel, public API v1 with docs.

---

### Phase 13 — Performance, Reliability & Scale
**Covers:** Admin #8, #9, #13; General #1, #2

- Backup scheduling + disaster-recovery restore drill — Admin #8.
- Prometheus/Grafana instrumentation for request latency, queue depth, ML service health — Admin #9.
- Load testing (k6 or Locust) to validate the platform holds up as institutions/students grow — Admin #13.
- Performance target: dashboard/report queries respond in under 2 seconds even as data grows — General #1 (add DB indexing, caching, and pagination as needed).
- High-availability setup, health checks, and automated failover/recovery plan — General #2.

**Deliverables:** backup/restore runbook, Grafana dashboards, load-test report, caching layer.

---

### Phase 14 — Testing, CI/CD & Deployment
- PHPUnit/Pest feature & unit tests for Laravel; pytest for the Python service.
- Contract tests between Laravel and the Python service (so a model/API change can't silently break predictions).
- Staging environment mirroring production; blue-green or rolling deploy strategy.
- Final security review (dependency audit, OWASP checklist) before go-live.

**Deliverables:** CI pipeline with test gates, staging + production deployment, sign-off checklist.

---

## 4. Suggested Repository Layout

```
/app-backend            # Laravel
  app/Domain/...         # institutions, academics, alerts, recommendations, notifications
  app/Services/Prediction/PredictionServiceClient.php
  app/Http/Controllers/Api/...
/ml-service              # Python / FastAPI
  app/main.py
  app/models/            # trained model artifacts (or pulled from MLflow at boot)
  app/routers/predict.py, explain.py, retrain.py
  app/nlp/                # sentiment, chatbot grounding, pattern mining
  tests/
docker-compose.yml
```

## 5. Why Python Is Needed Here (Summary)

Everything under **Actor 6 — System/AI Engine** (prediction modeling, explainable AI/SHAP, NLP sentiment analysis, bias/fairness auditing, automated retraining with drift detection) depends on the Python data-science ecosystem (scikit-learn, SHAP, spaCy/transformers, MLflow) and has no mature equivalent in PHP. Laravel should **not** attempt to reimplement this logic; it should treat the Python service as an internal microservice it calls over HTTP, the same way it would call any third-party API.
