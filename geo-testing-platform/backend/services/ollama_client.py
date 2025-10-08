"""Ollama API client for local LLM inference"""

import os
import time
from typing import Dict, Any, Optional, List
import httpx
from loguru import logger


class OllamaClient:
    """Client for interacting with Ollama API"""

    def __init__(
        self,
        base_url: str = None,
        default_model: str = None,
        timeout: int = 300,
    ):
        self.base_url = base_url or os.getenv("OLLAMA_BASE_URL", "http://localhost:11434")
        self.default_model = default_model or os.getenv("OLLAMA_DEFAULT_MODEL", "mistral:7b-instruct")
        self.timeout = timeout

        logger.info(f"Initialized OllamaClient with base_url={self.base_url}, model={self.default_model}")

    async def generate(
        self,
        prompt: str,
        model: str = None,
        system: str = None,
        temperature: float = 0.7,
        max_tokens: int = 4000,
        stream: bool = False,
    ) -> Dict[str, Any]:
        """
        Generate text using Ollama

        Args:
            prompt: The prompt to send to the model
            model: Model to use (defaults to self.default_model)
            system: System prompt
            temperature: Sampling temperature (0.0 to 1.0)
            max_tokens: Maximum tokens to generate
            stream: Whether to stream the response

        Returns:
            Dict containing:
                - text: Generated text
                - model: Model used
                - generation_time: Time taken in milliseconds
                - metadata: Additional metadata
        """
        model = model or self.default_model

        start_time = time.time()

        payload = {
            "model": model,
            "prompt": prompt,
            "stream": stream,
            "options": {
                "temperature": temperature,
                "num_predict": max_tokens,
            }
        }

        if system:
            payload["system"] = system

        try:
            async with httpx.AsyncClient(timeout=self.timeout) as client:
                response = await client.post(
                    f"{self.base_url}/api/generate",
                    json=payload
                )
                response.raise_for_status()

            result = response.json()
            generation_time = int((time.time() - start_time) * 1000)

            return {
                "text": result.get("response", ""),
                "model": model,
                "generation_time": generation_time,
                "metadata": {
                    "done": result.get("done", False),
                    "context": result.get("context", []),
                    "total_duration": result.get("total_duration"),
                    "load_duration": result.get("load_duration"),
                    "prompt_eval_count": result.get("prompt_eval_count"),
                    "eval_count": result.get("eval_count"),
                }
            }

        except httpx.HTTPError as e:
            logger.error(f"HTTP error calling Ollama: {e}")
            raise
        except Exception as e:
            logger.error(f"Error calling Ollama: {e}")
            raise

    async def chat(
        self,
        messages: List[Dict[str, str]],
        model: str = None,
        temperature: float = 0.7,
        max_tokens: int = 4000,
    ) -> Dict[str, Any]:
        """
        Chat completion using Ollama

        Args:
            messages: List of message dicts with 'role' and 'content'
            model: Model to use
            temperature: Sampling temperature
            max_tokens: Maximum tokens to generate

        Returns:
            Dict containing generated response
        """
        model = model or self.default_model
        start_time = time.time()

        payload = {
            "model": model,
            "messages": messages,
            "stream": False,
            "options": {
                "temperature": temperature,
                "num_predict": max_tokens,
            }
        }

        try:
            async with httpx.AsyncClient(timeout=self.timeout) as client:
                response = await client.post(
                    f"{self.base_url}/api/chat",
                    json=payload
                )
                response.raise_for_status()

            result = response.json()
            generation_time = int((time.time() - start_time) * 1000)

            return {
                "text": result.get("message", {}).get("content", ""),
                "model": model,
                "generation_time": generation_time,
                "metadata": result
            }

        except httpx.HTTPError as e:
            logger.error(f"HTTP error calling Ollama chat: {e}")
            raise
        except Exception as e:
            logger.error(f"Error calling Ollama chat: {e}")
            raise

    async def list_models(self) -> List[str]:
        """List available Ollama models"""
        try:
            async with httpx.AsyncClient(timeout=30) as client:
                response = await client.get(f"{self.base_url}/api/tags")
                response.raise_for_status()

            result = response.json()
            models = [model.get("name") for model in result.get("models", [])]

            logger.info(f"Available models: {models}")
            return models

        except Exception as e:
            logger.error(f"Error listing Ollama models: {e}")
            raise

    async def check_health(self) -> bool:
        """Check if Ollama is running"""
        try:
            async with httpx.AsyncClient(timeout=5) as client:
                response = await client.get(f"{self.base_url}/api/tags")
                return response.status_code == 200
        except Exception:
            return False


# Example usage
if __name__ == "__main__":
    import asyncio

    async def test():
        client = OllamaClient()

        # Check health
        is_healthy = await client.check_health()
        print(f"Ollama healthy: {is_healthy}")

        if is_healthy:
            # List models
            models = await client.list_models()
            print(f"Available models: {models}")

            # Generate text
            result = await client.generate(
                prompt="What is generative engine optimization?",
                temperature=0.7
            )
            print(f"\nGenerated text:\n{result['text']}")
            print(f"\nGeneration time: {result['generation_time']}ms")

    asyncio.run(test())
