from sqlalchemy import Column, Integer, String, Text, DateTime, Float, func
from database import Base

class MessageLog(Base):
    __tablename__ = "message_logs"
    id = Column(Integer, primary_key=True, index=True)
    sender_id = Column(String, index=True)
    sender_name = Column(String)
    message_text = Column(Text)
    sentiment = Column(String)
    intent = Column(String)
    confidence = Column(Float, nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    status = Column(String, default="pending")
    resolved_by = Column(String, nullable=True)

class ManualCorrection(Base):
    __tablename__ = "manual_corrections"
    id = Column(Integer, primary_key=True, index=True)
    message_text = Column(Text)
    original_sentiment = Column(String, nullable=True)
    corrected_sentiment = Column(String, nullable=True)
    original_intent = Column(String, nullable=True)
    corrected_intent = Column(String, nullable=True)
    admin_id = Column(String)