"""Real HTTP checks of the PHP endpoint with isolated WordPress API doubles."""
import hashlib
import json
import os
from pathlib import Path
import socket
import subprocess
import tempfile
import time
import unittest
from urllib.error import HTTPError
from urllib.request import Request, urlopen


class FeedHttpTests(unittest.TestCase):
    def test_protected_delivery(self):
        with tempfile.TemporaryDirectory(prefix="maruderm-kasta-http-") as temporary:
            root = Path(temporary)
            (root / "public").mkdir()
            storage = root / "private" / ("kasta-" + hashlib.sha256(b"https://wp.maruderm.com.ua").hexdigest()[:16])
            storage.mkdir(parents=True)
            feed = storage / "products.xml"
            content = b'<?xml version="1.0"?><yml_catalog><shop/></yml_catalog>'
            feed.write_bytes(content)
            options = {"maruderm_kasta_settings": {"enabled": True, "key": "a" * 64, "max_age_hours": 1}}
            (root / "options.json").write_text(json.dumps(options))
            with socket.socket() as sock:
                sock.bind(("127.0.0.1", 0))
                port = sock.getsockname()[1]
            server = subprocess.Popen(["php", "-S", f"127.0.0.1:{port}", "-t", str(root / "public"), str(Path(__file__).with_name("http-fixture.php"))],
                env={**os.environ, "KASTA_TEST_ROOT": temporary}, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            try:
                for _ in range(100):
                    try:
                        with socket.create_connection(("127.0.0.1", port), timeout=0.1):
                            break
                    except OSError:
                        time.sleep(0.02)
                path = "/kasta-feed/" + "a" * 64 + "/products.xml"

                def request(path, method="GET"):
                    try:
                        response = urlopen(Request(f"http://127.0.0.1:{port}" + path, method=method), timeout=5)
                    except HTTPError as error:
                        response = error
                    with response:
                        return response.status, response.headers, response.read()

                status, headers, body = request(path)
                self.assertEqual(status, 200)
                self.assertEqual(body, content)
                self.assertIn("application/xml", headers["Content-Type"])
                self.assertIn("no-store", headers["Cache-Control"])
                self.assertEqual(headers["X-Content-Type-Options"], "nosniff")
                self.assertIn("noindex", headers["X-Robots-Tag"])
                self.assertEqual(request(path, "HEAD")[2], b"")
                self.assertEqual(request(path, "POST")[0], 405)
                for bad in ["/kasta-feed", "/kasta-feed/", path.replace("products.xml", ""), path.replace("products.xml", "report.json"), path.replace("a" * 64, "b" * 64), "/private/", "/options.json"]:
                    with self.subTest(path=bad):
                        self.assertEqual(request(bad)[0], 404)
                options["maruderm_kasta_settings"]["enabled"] = False
                (root / "options.json").write_text(json.dumps(options))
                self.assertEqual(request(path)[0], 404)
                options["maruderm_kasta_settings"]["enabled"] = True
                options["maruderm_kasta_settings"]["key"] = "b" * 64
                (root / "options.json").write_text(json.dumps(options))
                self.assertEqual(request(path)[0], 404)
                path = path.replace("a" * 64, "b" * 64)
                os.utime(feed, (time.time() - 7200, time.time() - 7200))
                self.assertEqual(request(path)[0], 503)
                feed.unlink()
                self.assertEqual(request(path)[0], 503)
            finally:
                server.terminate()
                server.wait(timeout=5)


if __name__ == "__main__":
    unittest.main()
