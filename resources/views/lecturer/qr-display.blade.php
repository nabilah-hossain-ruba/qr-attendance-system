<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QR Code - {{ $session->course->course_code }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Arial', sans-serif;
        }
        .qr-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .course-info {
            margin-bottom: 1.5rem;
        }
        .qr-code {
            margin: 1.5rem 0;
            padding: 1rem;
            border: 2px dashed #ddd;
            border-radius: 10px;
            background: #f8f9fa;
        }
        .timer {
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
            margin-top: 1rem;
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .refresh-btn {
            position: fixed;
            top: 20px;
            right: 20px;
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <div class="course-info">
            <h2 class="mb-1">{{ $session->course->course_code }}</h2>
            <h4 class="text-muted mb-2">{{ $session->course->course_name }}</h4>
            <p class="mb-1"><strong>Section:</strong> {{ $session->course->section }}</p>
            <p class="mb-1"><strong>Date:</strong> {{ $session->date }}</p>
            <p class="mb-0">
                <span class="badge bg-{{ $session->status == 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($session->status) }}
                </span>
            </p>
        </div>

        @if($session->status == 'active' && $session->qr_expiry > now())
            <div class="qr-code">
                <!-- Primary QR Code using QR Server API (more reliable) -->
                <div id="primary-qr">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($session->qr_code) }}&format=png" 
                         alt="QR Code" 
                         style="max-width: 250px; height: auto; border: 1px solid #ddd; border-radius: 8px;">
                </div>
                <!-- Fallback Canvas QR Code -->
                <div id="fallback-qr" style="display: none;">
                    <canvas id="qrcode"></canvas>
                </div>
                <div class="mt-2">
                    <small class="text-muted">Scan with your phone to mark attendance</small>
                </div>
            </div>

            <div class="timer" id="timer">
                <i class="bi bi-clock"></i> 
                <span id="countdown">{{ now()->diffInMinutes($session->qr_expiry) }}m</span> remaining
            </div>

            <div class="mt-3">
                <p class="mb-1"><strong>QR Code:</strong></p>
                <code class="bg-light p-2 rounded">{{ $session->qr_code }}</code>
            </div>

            <div class="mt-3">
                <p class="text-success status-active">
                    <i class="bi bi-check-circle"></i> Active Session - Ready for Attendance
                </p>
            </div>
        @else
            <div class="alert alert-warning">
                <h5><i class="bi bi-exclamation-triangle"></i> Session Expired</h5>
                <p class="mb-0">This QR code has expired. Please ask your lecturer to generate a new session.</p>
            </div>
        @endif

        <div class="mt-4">
            <small class="text-muted">
                Attendance: {{ $session->attendances->count() }} students marked present
            </small>
        </div>

        <div class="mt-3">
            <a href="{{ route('lecturer.session.show', $session->id) }}" class="btn btn-primary btn-sm me-2">
                <i class="bi bi-arrow-left"></i> Back to Session
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="btn btn-info refresh-btn" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise"></i> Refresh
    </button>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sessionStatus = "{{ $session->status }}";
            const qrExpiry = new Date("{{ $session->qr_expiry }}");
            const qrCodeValue = "{{ $session->qr_code }}";
            const sessionId = "{{ $session->id }}";
            const now = new Date();

            console.log('Session Status:', sessionStatus);
            console.log('QR Expiry:', qrExpiry);
            console.log('QR Code Value:', qrCodeValue);
            console.log('Current Time:', now);

            if(sessionStatus === 'active' && qrExpiry > now) {
                // Check if primary QR image loads successfully
                const primaryQrImg = document.querySelector('#primary-qr img');
                if (primaryQrImg) {
                    primaryQrImg.onerror = function() {
                        console.log('Primary QR image failed to load, trying fallback');
                        document.getElementById('primary-qr').style.display = 'none';
                        document.getElementById('fallback-qr').style.display = 'block';
                        generateCanvasQR();
                    };
                    
                    primaryQrImg.onload = function() {
                        console.log('Primary QR image loaded successfully');
                    };
                }

                // Update countdown timer and auto-close session
                function updateTimer() {
                    const now = new Date();
                    const timeDiff = qrExpiry - now;
                    
                    if (timeDiff <= 0) {
                        document.getElementById('countdown').textContent = 'Expired';
                        document.getElementById('timer').className = 'timer text-danger';
                        clearInterval(timerInterval);
                        
                        // Automatically close the session
                        closeSession();
                        return;
                    }
                    
                    const minutes = Math.floor(timeDiff / (1000 * 60));
                    const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);
                    
                    document.getElementById('countdown').textContent = 
                        minutes + 'm ' + seconds + 's';
                }

                // Function to close session automatically
                function closeSession() {
                    fetch(`/lecturer/session/${sessionId}/close`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            console.log('Session closed automatically');
                            // Show expired message
                            document.querySelector('.qr-container').innerHTML = `
                                <div class="alert alert-warning">
                                    <h5><i class="bi bi-exclamation-triangle"></i> Session Expired</h5>
                                    <p class="mb-0">This QR code has expired and the session has been automatically closed.</p>
                                </div>
                                <div class="mt-3">
                                    <a href="/lecturer/dashboard" class="btn btn-primary">
                                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                                    </a>
                                </div>
                            `;
                        } else {
                            console.error('Failed to close session');
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error closing session:', error);
                        location.reload();
                    });
                }

                const timerInterval = setInterval(updateTimer, 1000);
                updateTimer(); // Initial call
            } else {
                console.log('Session not active or expired');
            }
        });

        // Function to generate canvas QR as fallback
        function generateCanvasQR() {
            const qrCodeValue = "{{ $session->qr_code }}";
            const canvas = document.getElementById('qrcode');
            
            if (typeof QRCode !== 'undefined' && canvas) {
                QRCode.toCanvas(canvas, qrCodeValue, {
                    width: 250,
                    margin: 2,
                    color: {
                        dark: '#000000',
                        light: '#ffffff'
                    }
                }, function(error) {
                    if (error) {
                        console.error('Canvas QR Code generation failed:', error);
                        canvas.parentElement.innerHTML = '<div class="alert alert-danger">Failed to generate QR code</div>';
                    } else {
                        console.log('Canvas QR Code generated successfully');
                    }
                });
            }
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
