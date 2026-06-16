from sqlalchemy import Column, Integer, String, Text, DateTime, Float, func
from database import Base


class Message(Base):
    """
    Diselaraskan dengan tabel 'messages' milik Laravel dashboard.
    Field 'message' (bukan 'message_text') agar konsisten dengan schema Laravel.
    """
    __tablename__ = "messages"
    id          = Column(Integer, primary_key=True, index=True)
    user_id     = Column(Integer, nullable=True, index=True)  # FK ke users (Laravel)
    sender_id   = Column(String, index=True)
    sender_name = Column(String, nullable=True)
    message     = Column(Text)                                 # sebelumnya: message_text
    sentiment   = Column(String)
    intent      = Column(String)
    confidence  = Column(Float, nullable=True)
    suggestion  = Column(Text, nullable=True)
    status      = Column(String, default="pending")
    resolved_by = Column(Integer, nullable=True)
    created_at  = Column(DateTime(timezone=True), default=func.now())
    updated_at  = Column(DateTime(timezone=True), default=func.now(), onupdate=func.now())


class ManualCorrection(Base):
    __tablename__ = "manual_corrections"
    id                  = Column(Integer, primary_key=True, index=True)
    message_text        = Column(Text)
    original_sentiment  = Column(String, nullable=True)
    corrected_sentiment = Column(String, nullable=True)
    original_intent     = Column(String, nullable=True)
    corrected_intent    = Column(String, nullable=True)
    admin_id            = Column(String)
    created_at          = Column(DateTime(timezone=True), default=func.now())
    updated_at          = Column(DateTime(timezone=True), default=func.now(), onupdate=func.now())

class KnowledgeBase(Base):
    __tablename__ = "knowledge_bases"
    id          = Column(Integer, primary_key=True, index=True)
    user_id     = Column(Integer, index=True)
    content     = Column(Text)
    metadata_col = Column("metadata", String, nullable=True) # JSON stored as string/text depending on db driver
    created_at  = Column(DateTime(timezone=True), default=func.now())
    updated_at  = Column(DateTime(timezone=True), default=func.now(), onupdate=func.now())