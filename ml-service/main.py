from fastapi import FastAPI, Depends, HTTPException, Security, BackgroundTasks
from fastapi.security import APIKeyHeader
from pydantic import BaseModel
from typing import List, Dict, Any
import numpy as np
from model import MLModelWrapper
from nlp import NLPWrapper

app = FastAPI(title="SBA ML Service", version="1.0.0")
ml_model = MLModelWrapper()
nlp_model = NLPWrapper()

# Internal Auth (Simple API Key for now)
API_KEY = "internal_secret_key_for_sba"
api_key_header = APIKeyHeader(name="X-API-Key", auto_error=False)

def get_api_key(api_key_header: str = Security(api_key_header)):
    if api_key_header == API_KEY:
        return api_key_header
    raise HTTPException(status_code=403, detail="Could not validate credentials")

class HealthResponse(BaseModel):
    status: str
    message: str

class StudentFeatures(BaseModel):
    student_id: int
    attendance_rate: float
    average_grade: float
    behavior_score: float
    participation_rate: float

class PredictionResponse(BaseModel):
    student_id: int
    risk_score: float
    risk_level: str

class ExplanationResponse(BaseModel):
    student_id: int
    factors: Dict[str, float]

class SentimentRequest(BaseModel):
    text: str

class SentimentResponse(BaseModel):
    label: str
    score: float

class ChatRequest(BaseModel):
    query: str
    context: dict

class ChatResponse(BaseModel):
    response: str

@app.get("/health", response_model=HealthResponse)
async def health_check():
    return {"status": "ok", "message": "ML Service is running"}

@app.post("/predict", response_model=PredictionResponse)
async def predict_risk(data: StudentFeatures, api_key: str = Depends(get_api_key)):
    features = np.array([[data.attendance_rate, data.average_grade, data.behavior_score, data.participation_rate]])
    risk_score = ml_model.predict(features)
    
    level = "low"
    if risk_score > 75:
        level = "high"
    elif risk_score > 40:
        level = "medium"

    return PredictionResponse(
        student_id=data.student_id,
        risk_score=float(risk_score),
        risk_level=level
    )

@app.post("/explain", response_model=ExplanationResponse)
async def explain_risk(data: StudentFeatures, api_key: str = Depends(get_api_key)):
    features = np.array([[data.attendance_rate, data.average_grade, data.behavior_score, data.participation_rate]])
    shap_values = ml_model.explain(features)
    
    feature_names = ["attendance_rate", "average_grade", "behavior_score", "participation_rate"]
    factors = {name: float(val) for name, val in zip(feature_names, shap_values[0])}
    
    return ExplanationResponse(
        student_id=data.student_id,
        factors=factors
    )

@app.post("/retrain")
async def retrain_model(background_tasks: BackgroundTasks, api_key: str = Depends(get_api_key)):
    background_tasks.add_task(ml_model.retrain)
    return {"status": "accepted", "message": "Retraining job started in background"}

@app.post("/nlp/sentiment", response_model=SentimentResponse)
async def analyze_sentiment(data: SentimentRequest, api_key: str = Depends(get_api_key)):
    result = nlp_model.analyze_sentiment(data.text)
    return SentimentResponse(**result)

@app.post("/nlp/chatbot", response_model=ChatResponse)
async def chat(data: ChatRequest, api_key: str = Depends(get_api_key)):
    response_text = nlp_model.chat_response(data.query, data.context)
    return ChatResponse(response=response_text)
