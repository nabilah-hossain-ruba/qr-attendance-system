@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Scan QR Code</span>
                    <a href="{{ route('student.dashboard') }}" class="btn btn-sm btn-secondary">Back to Dashboard</a>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="text-center mb-4">
                        <p class="lead">Scan the QR code provided by your lecturer to mark your attendance</p>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mx-auto">
                            <div id="qr-reader" class="border rounded mb-3" style="width: 100%; min-height: 300px;"></div>
                            
                            <div id="qr-reader-results" class="d-none alert alert-info">
                                Processing...
                            </div>

                            <div class="text-center mt-3">
                                <button id="start-scanner" class="btn btn-primary">Start Scanner</button>
                                <button id="stop-scanner" class="btn btn-danger d-none">Stop Scanner</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="card">
                            <div class="card-header bg-light">Manual Entry</div>
                            <div class="card-body">
                                <form id="manual-qr-form">
                                    <div class="form-group">
                                        <label for="qr-code">QR Code Value</label>
                                        <input type="text" class="form-control" id="qr-code" name="qr_code" placeholder="Enter QR code value manually">
                                    </div>
                                    <button type="submit" class="btn btn-primary mt-3">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const html5QrCode = new Html5Qrcode("qr-reader");
        const qrResultElement = document.getElementById('qr-reader-results');
        const startButton = document.getElementById('start-scanner');
        const stopButton = document.getElementById('stop-scanner');
        let scanning = false;

        function onScanSuccess(decodedText, decodedResult) {
            if (scanning) {
                // Stop scanning
                html5QrCode.stop().then(() => {
                    scanning = false;
                    startButton.classList.remove('d-none');
                    stopButton.classList.add('d-none');
                    
                    // Show processing message
                    qrResultElement.classList.remove('d-none');
                    qrResultElement.classList.remove('alert-danger');
                    qrResultElement.classList.add('alert-info');
                    qrResultElement.innerHTML = 'Processing QR code...';
                    
                    // Send to server
                    processQrCode(decodedText);
                });
            }
        }

        function onScanFailure(error) {
            // Handle scan failure if needed
            console.warn(`QR code scanning failed: ${error}`);
        }

        startButton.addEventListener('click', function() {
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            html5QrCode.start(
                { facingMode: "environment" }, 
                config, 
                onScanSuccess, 
                onScanFailure
            ).then(() => {
                scanning = true;
                startButton.classList.add('d-none');
                stopButton.classList.remove('d-none');
                qrResultElement.classList.add('d-none');
            }).catch(err => {
                qrResultElement.classList.remove('d-none');
                qrResultElement.classList.remove('alert-info');
                qrResultElement.classList.add('alert-danger');
                qrResultElement.innerHTML = `Error starting scanner: ${err}`;
            });
        });

        stopButton.addEventListener('click', function() {
            if (scanning) {
                html5QrCode.stop().then(() => {
                    scanning = false;
                    startButton.classList.remove('d-none');
                    stopButton.classList.add('d-none');
                });
            }
        });

        // Manual form submission
        document.getElementById('manual-qr-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const qrCode = document.getElementById('qr-code').value.trim();
            
            if (qrCode) {
                qrResultElement.classList.remove('d-none');
                qrResultElement.classList.remove('alert-danger');
                qrResultElement.classList.add('alert-info');
                qrResultElement.innerHTML = 'Processing QR code...';
                
                processQrCode(qrCode);
            }
        });

        function processQrCode(qrCode) {
            // Send to server using fetch API
            fetch('{{ route("attendance.mark") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ qr_code: qrCode })
            })
            .then(response => response.json())
            .then(data => {
                qrResultElement.classList.remove('d-none');
                
                if (data.success) {
                    qrResultElement.classList.remove('alert-info', 'alert-danger');
                    qrResultElement.classList.add('alert-success');
                    qrResultElement.innerHTML = `
                        <h5>Attendance Marked Successfully!</h5>
                        <p><strong>Course:</strong> ${data.data.course}</p>
                        <p><strong>Date:</strong> ${data.data.date}</p>
                        <p><strong>Time:</strong> ${data.data.time}</p>
                    `;
                    
                    // Clear the manual input
                    document.getElementById('qr-code').value = '';
                } else {
                    qrResultElement.classList.remove('alert-info', 'alert-success');
                    qrResultElement.classList.add('alert-danger');
                    qrResultElement.innerHTML = `Error: ${data.message}`;
                }
            })
            .catch(error => {
                qrResultElement.classList.remove('d-none', 'alert-info', 'alert-success');
                qrResultElement.classList.add('alert-danger');
                qrResultElement.innerHTML = `Error: ${error.message || 'Failed to process QR code'}`;
            });
        }
    });
</script>
@endsection