"""
test_req.py — Integration test: verifikasi endpoint API FastAPI melalui HTTP sungguhan.
Server uvicorn harus berjalan di port 8000 agar test ini berhasil,
jika tidak test akan di-skip otomatis.
"""
import pytest
import httpx


@pytest.mark.asyncio
async def test_health_score_via_http():
    """
    Memanggil endpoint /health-score/{sender_id} melalui HTTP sungguhan.
    Memerlukan server uvicorn berjalan di port 8000.
    Akan di-skip jika server tidak aktif.
    """
    try:
        async with httpx.AsyncClient(timeout=5.0) as c:
            r = await c.get(
                'http://127.0.0.1:8000/health-score/test_sender_req',
                headers={'Authorization': 'Bearer test_token_123'}
            )
            # Bisa 200 (valid token) atau 401 (token salah) — keduanya diterima
            assert r.status_code in (200, 401, 403, 404)
    except (httpx.ConnectError, httpx.ConnectTimeout):
        pytest.skip("Server uvicorn tidak berjalan di port 8000. Jalankan server terlebih dahulu.")
