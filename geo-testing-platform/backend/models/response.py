"""Response model for generated answers"""

from sqlalchemy import Column, Integer, Text, JSON, ForeignKey
from sqlalchemy.orm import relationship
from .base import Base, TimestampMixin


class Response(Base, TimestampMixin):
    """Generated response from LLM"""

    __tablename__ = "responses"

    id = Column(Integer, primary_key=True, index=True)
    experiment_run_id = Column(Integer, ForeignKey("experiment_runs.id"), nullable=False, index=True)

    # Generated response
    response_text = Column(Text, nullable=False)
    response_length = Column(Integer, nullable=False)  # word count

    # Source tracking
    sources_used = Column(JSON, default=list)  # List of source IDs/titles used
    source_order = Column(JSON, default=list)  # Order sources were presented to model

    # Citation extraction
    cited_sources = Column(JSON, default=list)  # Which sources were cited
    citation_count = Column(Integer, default=0)
    has_target_citation = Column(Integer, default=0)  # 1 if target source was cited, 0 if not

    # Raw model output
    raw_output = Column(JSON, default=dict)  # Full model response including metadata

    # Relationships
    metrics = relationship("Metrics", back_populates="response", uselist=False, cascade="all, delete-orphan")

    def __repr__(self):
        return f"<Response(id={self.id}, run_id={self.experiment_run_id}, citations={self.citation_count})>"
