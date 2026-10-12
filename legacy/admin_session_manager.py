import csv
import datetime
from enum import Enum
from typing import Dict, List, Optional

class SessionStatus(Enum):
    SCHEDULED = "Scheduled"
    IN_PROGRESS = "In_Progress"
    COMPLETED = "Completed"
    CANCELLED = "Cancelled"
    POSTPONED = "Postponed"

class SessionManager:
    def __init__(self, csv_file_path: str):
        self.csv_file_path = csv_file_path
        self.headers = [
            'SL', 'Course', 'Faculty', 'Section', 'Day', 'Start_Time', 
            'End_Time', 'Room', 'Status', 'Session_ID', 'Date_Created', 
            'Last_Updated', 'Lecturer_ID', 'Notes'
        ]
    
    def read_sessions(self) -> List[Dict]:
        """Read all sessions from CSV file"""
        sessions = []
        try:
            with open(self.csv_file_path, 'r', newline='', encoding='utf-8') as file:
                reader = csv.DictReader(file)
                sessions = list(reader)
        except FileNotFoundError:
            print(f"CSV file not found: {self.csv_file_path}")
        return sessions
    
    def write_sessions(self, sessions: List[Dict]):
        """Write sessions back to CSV file"""
        with open(self.csv_file_path, 'w', newline='', encoding='utf-8') as file:
            writer = csv.DictWriter(file, fieldnames=self.headers)
            writer.writeheader()
            writer.writerows(sessions)
    
    def update_session_status(self, session_id: str, new_status: SessionStatus, notes: str = "") -> bool:
        """Update session status and timestamp"""
        sessions = self.read_sessions()
        updated = False
        
        for session in sessions:
            if session['Session_ID'] == session_id:
                session['Status'] = new_status.value
                session['Last_Updated'] = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                if notes:
                    current_notes = session.get('Notes', '')
                    session['Notes'] = f"{current_notes}; {notes}" if current_notes else notes
                updated = True
                break
        
        if updated:
            self.write_sessions(sessions)
            print(f"Session {session_id} status updated to {new_status.value}")
        else:
            print(f"Session {session_id} not found")
        
        return updated
    
    def end_session(self, lecturer_id: str, course: str, section: str, day: str) -> bool:
        """End a session when lecturer finishes"""
        sessions = self.read_sessions()
        current_time = datetime.datetime.now()
        updated_sessions = []
        
        for session in sessions:
            if (session['Lecturer_ID'] == lecturer_id and 
                session['Course'] == course and 
                session['Section'] == section and 
                session['Day'].upper() == day.upper() and
                session['Status'] == SessionStatus.IN_PROGRESS.value):
                
                session['Status'] = SessionStatus.COMPLETED.value
                session['Last_Updated'] = current_time.strftime("%Y-%m-%d %H:%M:%S")
                session['Notes'] = f"{session.get('Notes', '')}; Ended at {current_time.strftime('%H:%M:%S')}"
                updated_sessions.append(session['Session_ID'])
        
        if updated_sessions:
            self.write_sessions(sessions)
            print(f"Ended sessions: {', '.join(updated_sessions)}")
            return True
        else:
            print(f"No active sessions found for {lecturer_id} - {course} Section {section} on {day}")
            return False
    
    def start_session(self, session_id: str) -> bool:
        """Start a session"""
        return self.update_session_status(
            session_id, 
            SessionStatus.IN_PROGRESS, 
            f"Started at {datetime.datetime.now().strftime('%H:%M:%S')}"
        )
    
    def get_active_sessions(self) -> List[Dict]:
        """Get all currently active sessions"""
        sessions = self.read_sessions()
        return [s for s in sessions if s['Status'] == SessionStatus.IN_PROGRESS.value]
    
    def get_sessions_by_lecturer(self, lecturer_id: str) -> List[Dict]:
        """Get all sessions for a specific lecturer"""
        sessions = self.read_sessions()
        return [s for s in sessions if s['Lecturer_ID'] == lecturer_id]
    
    def get_sessions_by_status(self, status: SessionStatus) -> List[Dict]:
        """Get all sessions with specific status"""
        sessions = self.read_sessions()
        return [s for s in sessions if s['Status'] == status.value]
    
    def generate_daily_report(self, date: str = None) -> Dict:
        """Generate daily session report"""
        if not date:
            date = datetime.datetime.now().strftime("%Y-%m-%d")
        
        sessions = self.read_sessions()
        today_sessions = [s for s in sessions if s['Date_Created'] == date]
        
        report = {
            'date': date,
            'total_sessions': len(today_sessions),
            'by_status': {},
            'by_lecturer': {}
        }
        
        for session in today_sessions:
            status = session['Status']
            lecturer = session['Lecturer_ID']
            
            report['by_status'][status] = report['by_status'].get(status, 0) + 1
            report['by_lecturer'][lecturer] = report['by_lecturer'].get(lecturer, 0) + 1
        
        return report

# Example usage functions
def main():
    manager = SessionManager('C:\\QR_Attendance\\course_schedule_improved.csv')
    
    # Example: Start a session
    # manager.start_session('ACT201_SHO_01_001')
    
    # Example: End a session
    # manager.end_session('SHO001', 'ACT201', '01', 'SUNDAY')
    
    # Example: Get active sessions
    active_sessions = manager.get_active_sessions()
    print(f"Active sessions: {len(active_sessions)}")
    
    # Example: Generate daily report
    report = manager.generate_daily_report()
    print("Daily Report:", report)

if __name__ == "__main__":
    main()
