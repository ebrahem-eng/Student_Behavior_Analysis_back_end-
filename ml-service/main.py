from fastapi import FastAPI, Depends, HTTPException, Security
from fastapi.security import APIKeyHeader
from pydantic import BaseModel

app = FastAPI(title="SBA ML Service", version="1.0.0")

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

@app.get("/health", response_model=HealthResponse)
async def health_check():
    return {"status": "ok", "message": "ML Service is running"}

@app.get("/secure-health", response_model=HealthResponse)
async def secure_health_check(api_key: str = Depends(get_api_key)):
    return {"status": "ok", "message": "Authenticated ML Service is running"}
