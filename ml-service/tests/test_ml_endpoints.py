import pytest
from fastapi.testclient import TestClient
import sys
import os

# Add parent directory to sys.path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from main import app, API_KEY

client = TestClient(app)
headers = {"X-API-Key": API_KEY}

def test_health_check():
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json()["status"] == "ok"

def test_predict_risk():
    payload = {
        "student_id": 101,
        "attendance_rate": 80.0,
        "average_grade": 75.0,
        "behavior_score": 85.0,
        "participation_rate": 70.0
    }
    response = client.post("/predict", json=payload, headers=headers)
    assert response.status_code == 200
    data = response.json()
    assert data["student_id"] == 101
    assert "risk_score" in data
    assert data["risk_level"] in ["low", "medium", "high"]

def test_explain_risk():
    payload = {
        "student_id": 101,
        "attendance_rate": 80.0,
        "average_grade": 75.0,
        "behavior_score": 85.0,
        "participation_rate": 70.0
    }
    response = client.post("/explain", json=payload, headers=headers)
    assert response.status_code == 200
    data = response.json()
    assert data["student_id"] == 101
    assert "factors" in data

def test_nlp_sentiment():
    payload = {"text": "Student is feeling happy and engaged with the class."}
    response = client.post("/nlp/sentiment", json=payload, headers=headers)
    assert response.status_code == 200
    data = response.json()
    assert data["label"] == "POSITIVE"

def test_nlp_chatbot():
    payload = {
        "query": "Is the student performing well?",
        "context": {"student_id": 101, "risk_level": "low"}
    }
    response = client.post("/nlp/chatbot", json=payload, headers=headers)
    assert response.status_code == 200
    assert "response" in response.json()

def test_project_future_performance():
    payload = {
        "student_id": 101,
        "attendance_rate": 80.0,
        "average_grade": 75.0,
        "behavior_score": 85.0,
        "participation_rate": 70.0,
        "months_ahead": 3
    }
    response = client.post("/project", json=payload, headers=headers)
    assert response.status_code == 200
    data = response.json()
    assert len(data["projections"]) == 3

def test_get_metrics():
    response = client.get("/metrics", headers=headers)
    assert response.status_code == 200
    data = response.json()
    assert "accuracy" in data
    assert "f1_score" in data
