#!/usr/bin/env python3
import urllib.request
import urllib.error
import json
import time
import subprocess
import concurrent.futures
import os
import ssl

API_URL = os.environ.get('LARAVEL_API_URL', 'http://127.0.0.1:8000/api')
BEARER_TOKEN = os.environ.get('PING_DAEMON_TOKEN', 'default-secure-token-123')
CHECK_INTERVAL_SECONDS = 20

def fetch_devices():
    """Fetch the list of IP addresses to monitor from the Laravel API."""
    req = urllib.request.Request(f"{API_URL}/devices")
    req.add_header('Authorization', f'Bearer {BEARER_TOKEN}')
    req.add_header('Accept', 'application/json')

    try:
        # Create unverified context for local development if HTTPS is used
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE

        with urllib.request.urlopen(req, context=ctx, timeout=10) as response:
            data = json.loads(response.read().decode('utf-8'))
            return data
    except urllib.error.URLError as e:
        print(f"Error fetching devices: {e}")
        return []
    except Exception as e:
        print(f"Unexpected error fetching devices: {e}")
        return []

def ping_host(device):
    """Ping a single host and return the status."""
    ip = device.get('ip_address')
    if not ip:
        return None

    try:
        # Cross-platform ping timeout handling (Linux/Mac)
        # Using a timeout command wrapper to ensure it doesn't hang indefinitely
        # instead of relying solely on OS-specific ping arguments.
        # Suppress output using stdout/stderr to DEVNULL
        result = subprocess.run(
            ['ping', '-c', '1', ip],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            timeout=3
        )
        status = 'success' if result.returncode == 0 else 'failed'
        return {'ip': ip, 'status': status}
    except Exception as e:
        print(f"Error pinging {ip}: {e}")
        return {'ip': ip, 'status': 'failed'}

def post_results(results):
    """Send the ping results back to the Laravel API."""
    if not results:
        return

    req = urllib.request.Request(f"{API_URL}/ping-results", method='POST')
    req.add_header('Authorization', f'Bearer {BEARER_TOKEN}')
    req.add_header('Content-Type', 'application/json')
    req.add_header('Accept', 'application/json')

    data = json.dumps({'results': results}).encode('utf-8')

    try:
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE

        with urllib.request.urlopen(req, data=data, context=ctx, timeout=10) as response:
            if response.status not in (200, 201):
                print(f"Failed to post results. Status: {response.status}")
    except urllib.error.URLError as e:
        print(f"Error posting results: {e}")
    except Exception as e:
        print(f"Unexpected error posting results: {e}")

def main():
    print(f"Starting Ping Daemon. Interval: {CHECK_INTERVAL_SECONDS}s. API: {API_URL}")
    while True:
        start_time = time.time()

        devices = fetch_devices()

        if devices:
            results = []
            # Use ThreadPoolExecutor for concurrent pinging
            with concurrent.futures.ThreadPoolExecutor(max_workers=min(50, len(devices))) as executor:
                # Submit all ping tasks
                future_to_device = {executor.submit(ping_host, device): device for device in devices}

                # Collect results as they complete
                for future in concurrent.futures.as_completed(future_to_device):
                    res = future.result()
                    if res:
                        results.append(res)

            # Post the aggregated results back to the server
            post_results(results)
            print(f"Checked {len(results)} devices.")
        else:
            print("No devices found or failed to fetch.")

        # Calculate how long to sleep to maintain the desired interval
        elapsed = time.time() - start_time
        sleep_time = max(0, CHECK_INTERVAL_SECONDS - elapsed)
        time.sleep(sleep_time)

if __name__ == '__main__':
    main()
