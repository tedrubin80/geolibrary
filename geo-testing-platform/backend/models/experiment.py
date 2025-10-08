"""Experiment models for tracking test runs"""

from sqlalchemy import Column, Integer, String, Text, JSON, ForeignKey, Enum, Boolean
from sqlalchemy.orm import relationship
from .base import Base, TimestampMixin
import enum


class ExperimentStatus(str, enum.Enum):
    """Experiment status"""
    PENDING = "pending"
    RUNNING = "running"
    COMPLETED = "completed"
    FAILED = "failed"
    CANCELLED = "cancelled"


class Experiment(Base, TimestampMixin):
    """Experiment configuration and metadata"""

    __tablename__ = "experiments"

    id = Column(Integer, primary_key=True, index=True)

    name = Column(String(255), nullable=False)
    description = Column(Text)
    status = Column(Enum(ExperimentStatus), default=ExperimentStatus.PENDING, nullable=False)

    # Configuration
    model_name = Column(String(255), nullable=False)  # e.g., "mistral:7b-instruct"
    iterations = Column(Integer, default=10, nullable=False)
    geo_methods = Column(JSON, default=list)  # List of methods to test

    # Query selection
    query_ids = Column(JSON, default=list)  # List of query IDs to test
    domain_filter = Column(String(50))  # Filter by domain if specified

    # Results summary (calculated after completion)
    total_runs = Column(Integer, default=0)
    successful_runs = Column(Integer, default=0)
    failed_runs = Column(Integer, default=0)
    average_improvement = Column(JSON, default=dict)  # Per method

    # Relationships
    runs = relationship("ExperimentRun", back_populates="experiment", cascade="all, delete-orphan")

    def __repr__(self):
        return f"<Experiment(id={self.id}, name='{self.name}', status={self.status})>"


class ExperimentRun(Base, TimestampMixin):
    """Individual run within an experiment"""

    __tablename__ = "experiment_runs"

    id = Column(Integer, primary_key=True, index=True)
    experiment_id = Column(Integer, ForeignKey("experiments.id"), nullable=False, index=True)

    # Test configuration
    query_id = Column(Integer, ForeignKey("queries.id"), nullable=False)
    content_version_id = Column(Integer, ForeignKey("content_versions.id"), nullable=False)
    iteration = Column(Integer, nullable=False)  # Which iteration (1-10)

    # Run status
    status = Column(Enum(ExperimentStatus), default=ExperimentStatus.PENDING, nullable=False)
    error_message = Column(Text)

    # Execution metrics
    execution_time = Column(Integer)  # milliseconds
    model_used = Column(String(255))

    # Relationships
    experiment = relationship("Experiment", back_populates="runs")

    def __repr__(self):
        return f"<ExperimentRun(id={self.id}, query_id={self.query_id}, iteration={self.iteration})>"
