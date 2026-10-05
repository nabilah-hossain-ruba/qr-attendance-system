<!-- resources/views/attendances/create.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <title>Add Attendance</title>
</head>
<body>
    <h1>Add Attendance</h1>
    <form action="{{ url('/attendances') }}" method="POST">
        @csrf
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" required>
        <button type="submit">Add</button>
    </form>
    <a href="{{ url('/') }}">Back to List</a>
</body>
</html>
