<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Selection - QR Attendance System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .content {
            padding: 30px;
        }

        .search-section {
            margin-bottom: 30px;
        }

        .search-box {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box input, .search-box select {
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            flex: 1;
            min-width: 200px;
        }

        .search-box button {
            padding: 12px 30px;
            background: #4facfe;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .search-box button:hover {
            background: #3d8bfe;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .course-card {
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
        }

        .course-card:hover {
            border-color: #4facfe;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.15);
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .course-code {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .course-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-full {
            background: #f8d7da;
            color: #721c24;
        }

        .status-enrolled {
            background: #cce5ff;
            color: #004085;
        }

        .course-name {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #34495e;
        }

        .sections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .section-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .section-card:hover {
            border-color: #4facfe;
            background: #f8f9ff;
        }

        .section-card.selected {
            border-color: #28a745;
            background: #d4edda;
        }

        .section-card.full {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .section-info {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .section-number {
            font-weight: bold;
            color: #2c3e50;
        }

        .section-schedule {
            font-size: 12px;
            color: #6c757d;
        }

        .section-capacity {
            font-size: 11px;
            color: #6c757d;
            margin-top: 5px;
        }

        .enrollment-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }

        .enrolled-courses {
            margin-top: 20px;
        }

        .enrolled-course {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }

        .enrolled-course button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }

        .enroll-button {
            background: #28a745;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            width: 100%;
            transition: background 0.3s;
        }

        .enroll-button:hover:not(:disabled) {
            background: #218838;
        }

        .enroll-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
        }

        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .alert-info {
            background: #cce5ff;
            border-color: #b8daff;
            color: #004085;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Course Selection</h1>
            <div class="user-info" id="userInfo">
                <span id="userDetails">Loading user information...</span>
            </div>
        </div>

        <div class="content">
            <div id="alertContainer"></div>

            <div class="search-section">
                <h2>Search Courses</h2>
                <div class="search-box">
                    <input type="text" id="searchCourse" placeholder="Search by course code or name...">
                    <select id="departmentFilter">
                        <option value="">All Departments</option>
                    </select>
                    <select id="semesterFilter">
                        <option value="">All Semesters</option>
                    </select>
                    <button onclick="searchCourses()">Search</button>
                </div>
            </div>

            <div id="coursesContainer">
                <div class="loading">
                    <p>Loading available courses...</p>
                </div>
            </div>

            <div class="enrollment-summary">
                <h3>Your Enrollments</h3>
                <div id="enrolledCourses">
                    <div class="loading">Loading enrolled courses...</div>
                </div>
                <button class="enroll-button" id="enrollButton" onclick="processEnrollments()" disabled>
                    Enroll in Selected Courses
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentUser = null;
        let availableCourses = [];
        let selectedSections = [];
        let enrolledCourses = [];

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadUserInfo();
            loadAvailableCourses();
            loadEnrolledCourses();
            loadFilters();
        });

        async function loadUserInfo() {
            try {
                const response = await fetch('api/user_info.php');
                const user = await response.json();
                currentUser = user;
                
                document.getElementById('userDetails').innerHTML = `
                    <strong>${user.first_name} ${user.last_name}</strong> 
                    (${user.user_type}) - ID: ${user.student_id || user.lecturer_id}
                `;
            } catch (error) {
                showAlert('Error loading user information', 'error');
            }
        }

        async function loadAvailableCourses() {
            try {
                const response = await fetch('api/available_courses.php');
                const courses = await response.json();
                availableCourses = courses;
                renderCourses(courses);
            } catch (error) {
                showAlert('Error loading courses', 'error');
                document.getElementById('coursesContainer').innerHTML = 
                    '<p class="alert alert-error">Failed to load courses</p>';
            }
        }

        async function loadEnrolledCourses() {
            try {
                const response = await fetch('api/enrolled_courses.php');
                const courses = await response.json();
                enrolledCourses = courses;
                renderEnrolledCourses(courses);
            } catch (error) {
                showAlert('Error loading enrolled courses', 'error');
            }
        }

        async function loadFilters() {
            try {
                const response = await fetch('api/filter_options.php');
                const filters = await response.json();
                
                // Populate department filter
                const deptSelect = document.getElementById('departmentFilter');
                filters.departments.forEach(dept => {
                    deptSelect.innerHTML += `<option value="${dept}">${dept}</option>`;
                });

                // Populate semester filter
                const semSelect = document.getElementById('semesterFilter');
                filters.semesters.forEach(sem => {
                    semSelect.innerHTML += `<option value="${sem}">${sem}</option>`;
                });
            } catch (error) {
                console.error('Error loading filters:', error);
            }
        }

        function renderCourses(courses) {
            const container = document.getElementById('coursesContainer');
            
            if (courses.length === 0) {
                container.innerHTML = '<p class="alert alert-info">No courses found matching your criteria.</p>';
                return;
            }

            container.innerHTML = '<div class="courses-grid"></div>';
            const grid = container.querySelector('.courses-grid');

            courses.forEach(course => {
                const courseCard = document.createElement('div');
                courseCard.className = 'course-card';
                
                const isEnrolled = enrolledCourses.some(enrolled => enrolled.course_code === course.code);
                const statusClass = isEnrolled ? 'status-enrolled' : 
                                  course.available_capacity === 0 ? 'status-full' : 'status-available';
                const statusText = isEnrolled ? 'Enrolled' : 
                                 course.available_capacity === 0 ? 'Full' : 'Available';

                courseCard.innerHTML = `
                    <div class="course-header">
                        <div class="course-code">${course.code}</div>
                        <div class="course-status ${statusClass}">${statusText}</div>
                    </div>
                    <div class="course-name">${course.name}</div>
                    <div class="course-info">
                        <small>Credits: ${course.credits} | Department: ${course.department}</small>
                    </div>
                    <div class="sections-grid" id="sections-${course.code}">
                        ${renderSections(course.sections, course.code, isEnrolled)}
                    </div>
                `;

                grid.appendChild(courseCard);
            });
        }

        function renderSections(sections, courseCode, isEnrolled) {
            return sections.map(section => {
                const isFull = section.enrolled_count >= section.capacity;
                const isSelected = selectedSections.some(sel => 
                    sel.course_code === courseCode && sel.section_number === section.section_number
                );
                
                let cardClass = 'section-card';
                if (isFull && !isEnrolled) cardClass += ' full';
                if (isSelected) cardClass += ' selected';

                return `
                    <div class="${cardClass}" 
                         onclick="${!isFull || isEnrolled ? `toggleSection('${courseCode}', '${section.section_number}')` : ''}"
                         data-course="${courseCode}" 
                         data-section="${section.section_number}">
                        <div class="section-info">
                            <div class="section-number">Section ${section.section_number}</div>
                            <div class="section-schedule">
                                ${section.schedule ? section.schedule.map(s => 
                                    `${s.day} ${s.start_time}-${s.end_time}`
                                ).join('<br>') : 'Schedule TBA'}
                            </div>
                        </div>
                        <div class="section-capacity">
                            ${section.enrolled_count}/${section.capacity} students
                            ${section.lecturer_name ? `<br>Lecturer: ${section.lecturer_name}` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderEnrolledCourses(courses) {
            const container = document.getElementById('enrolledCourses');
            
            if (courses.length === 0) {
                container.innerHTML = '<p>You are not enrolled in any courses yet.</p>';
                return;
            }

            container.innerHTML = courses.map(course => `
                <div class="enrolled-course">
                    <div>
                        <strong>${course.course_code} - Section ${course.section_number}</strong>
                        <div style="font-size: 14px; color: #6c757d;">${course.course_name}</div>
                    </div>
                    <button onclick="dropCourse('${course.course_code}', '${course.section_number}')" 
                            title="Drop this course">Drop</button>
                </div>
            `).join('');
        }

        function toggleSection(courseCode, sectionNumber) {
            const existingIndex = selectedSections.findIndex(sel => 
                sel.course_code === courseCode && sel.section_number === sectionNumber
            );

            if (existingIndex >= 0) {
                selectedSections.splice(existingIndex, 1);
            } else {
                selectedSections.push({ course_code: courseCode, section_number: sectionNumber });
            }

            updateEnrollButton();
            updateSectionUI(courseCode, sectionNumber);
        }

        function updateSectionUI(courseCode, sectionNumber) {
            const sectionElement = document.querySelector(
                `[data-course="${courseCode}"][data-section="${sectionNumber}"]`
            );
            
            const isSelected = selectedSections.some(sel => 
                sel.course_code === courseCode && sel.section_number === sectionNumber
            );

            if (sectionElement) {
                if (isSelected) {
                    sectionElement.classList.add('selected');
                } else {
                    sectionElement.classList.remove('selected');
                }
            }
        }

        function updateEnrollButton() {
            const button = document.getElementById('enrollButton');
            const count = selectedSections.length;
            
            if (count === 0) {
                button.textContent = 'Enroll in Selected Courses';
                button.disabled = true;
            } else {
                button.textContent = `Enroll in ${count} Selected Course${count > 1 ? 's' : ''}`;
                button.disabled = false;
            }
        }

        async function processEnrollments() {
            if (selectedSections.length === 0) {
                showAlert('Please select at least one section to enroll in.', 'error');
                return;
            }

            const button = document.getElementById('enrollButton');
            const originalText = button.textContent;
            button.textContent = 'Processing...';
            button.disabled = true;

            try {
                const response = await fetch('api/enroll_courses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sections: selectedSections
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(`Successfully enrolled in ${result.enrolled_count} course(s)!`, 'success');
                    selectedSections = [];
                    loadEnrolledCourses();
                    loadAvailableCourses(); // Refresh to update capacity
                } else {
                    showAlert(`Enrollment failed: ${result.message}`, 'error');
                }
            } catch (error) {
                showAlert('Error processing enrollment', 'error');
            } finally {
                button.textContent = originalText;
                updateEnrollButton();
            }
        }

        async function dropCourse(courseCode, sectionNumber) {
            if (!confirm(`Are you sure you want to drop ${courseCode} Section ${sectionNumber}?`)) {
                return;
            }

            try {
                const response = await fetch('api/drop_course.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        course_code: courseCode,
                        section_number: sectionNumber
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Course dropped successfully!', 'success');
                    loadEnrolledCourses();
                    loadAvailableCourses(); // Refresh to update capacity
                } else {
                    showAlert(`Failed to drop course: ${result.message}`, 'error');
                }
            } catch (error) {
                showAlert('Error dropping course', 'error');
            }
        }

        function searchCourses() {
            const searchTerm = document.getElementById('searchCourse').value.toLowerCase();
            const department = document.getElementById('departmentFilter').value;
            const semester = document.getElementById('semesterFilter').value;

            const filteredCourses = availableCourses.filter(course => {
                const matchesSearch = !searchTerm || 
                    course.code.toLowerCase().includes(searchTerm) || 
                    course.name.toLowerCase().includes(searchTerm);
                
                const matchesDepartment = !department || course.department === department;
                const matchesSemester = !semester || course.semester === semester;

                return matchesSearch && matchesDepartment && matchesSemester;
            });

            renderCourses(filteredCourses);
        }

        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Add search functionality on Enter key
        document.getElementById('searchCourse').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCourses();
            }
        });
    </script>
</body>
</html>
