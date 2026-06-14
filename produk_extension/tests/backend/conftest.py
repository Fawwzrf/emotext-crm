"""
conftest.py — Shared pytest configuration and fixtures.
Allows all test_*.py files in this folder to import from backend/
without needing sys.path manipulation in every file.
"""
import sys
import os

# Tambahkan path backend ke sys.path agar semua test file bisa import langsung
BACKEND_DIR = os.path.join(os.path.dirname(__file__), "..", "..", "backend")
sys.path.insert(0, os.path.abspath(BACKEND_DIR))
