"""Metrics model for GEO performance measurements"""

from sqlalchemy import Column, Integer, Float, JSON, ForeignKey
from sqlalchemy.orm import relationship
from .base import Base, TimestampMixin


class Metrics(Base, TimestampMixin):
    """GEO performance metrics"""

    __tablename__ = "metrics"

    id = Column(Integer, primary_key=True, index=True)
    response_id = Column(Integer, ForeignKey("responses.id"), nullable=False, index=True, unique=True)

    # Core GEO Metrics (from KDD '24 paper)

    # Word Count (WC)
    word_count = Column(Integer, nullable=False)

    # Position Score (PS) - weighted by position
    position_score = Column(Float, nullable=False)

    # Position-Adjusted Word Count (PAWC)
    # Formula: PAWC = Σ(word_count_i × (1 / sqrt(position_i)))
    pawc = Column(Float, nullable=False)

    # Citation metrics
    cited = Column(Integer, default=0)  # 1 if cited, 0 if not
    citation_position = Column(Integer)  # Position where first cited (1-indexed, None if not cited)
    citation_frequency = Column(Integer, default=0)  # Number of times cited

    # Improvement metrics (compared to baseline)
    improvement_pawc = Column(Float)  # % improvement in PAWC vs baseline
    improvement_wc = Column(Float)  # % improvement in WC vs baseline
    improvement_ps = Column(Float)  # % improvement in PS vs baseline

    # Detailed position analysis
    position_breakdown = Column(JSON, default=dict)  # Word counts by position

    # Statistical significance
    confidence_interval_lower = Column(Float)
    confidence_interval_upper = Column(Float)

    # Additional metrics
    metadata = Column(JSON, default=dict)  # Extra metrics for analysis

    # Relationships
    response = relationship("Response", back_populates="metrics")

    def __repr__(self):
        return f"<Metrics(id={self.id}, PAWC={self.pawc:.2f}, cited={self.cited})>"
