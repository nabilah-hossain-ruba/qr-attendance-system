#!/usr/bin/env python3
"""
Admin CLI for QR Attendance Session Management
Usage: python admin_cli.py [command] [options]
"""

import argparse
import sys
from admin_session_manager import SessionManager, SessionStatus

def main():
    parser = argparse.ArgumentParser(description='QR Attendance Admin CLI')
    parser.add_argument('--csv', default='course_schedule_improved.csv', 
                       help='Path to CSV file')
    
    subparsers = parser.add_subparsers(dest='command', help='Available commands')
    
    # Start session command
    start_parser = subparsers.add_parser('start', help='Start a session')
    start_parser.add_argument('session_id', help='Session ID to start')
    
    # End session command
    end_parser = subparsers.add_parser('end', help='End a session')
    end_parser.add_argument('lecturer_id', help='Lecturer ID')
    end_parser.add_argument('course', help='Course code')
    end_parser.add_argument('section', help='Section number')
    end_parser.add_argument('day', help='Day of week')
    
    # List sessions command
    list_parser = subparsers.add_parser('list', help='List sessions')
    list_parser.add_argument('--status', help='Filter by status')
    list_parser.add_argument('--lecturer', help='Filter by lecturer ID')
    list_parser.add_argument('--active', action='store_true', help='Show only active sessions')
    
    # Update status command
    update_parser = subparsers.add_parser('update', help='Update session status')
    update_parser.add_argument('session_id', help='Session ID')
    update_parser.add_argument('status', choices=['Scheduled', 'In_Progress', 'Completed', 'Cancelled', 'Postponed'])
    update_parser.add_argument('--notes', help='Additional notes')
    
    # Report command
    report_parser = subparsers.add_parser('report', help='Generate reports')
    report_parser.add_argument('--date', help='Date for report (YYYY-MM-DD)')
    
    args = parser.parse_args()
    
    if not args.command:
        parser.print_help()
        return
    
    manager = SessionManager(args.csv)
    
    try:
        if args.command == 'start':
            success = manager.start_session(args.session_id)
            if success:
                print(f"✓ Session {args.session_id} started successfully")
            else:
                print(f"✗ Failed to start session {args.session_id}")
        
        elif args.command == 'end':
            success = manager.end_session(args.lecturer_id, args.course, args.section, args.day)
            if success:
                print(f"✓ Session ended successfully")
            else:
                print(f"✗ No active session found to end")
        
        elif args.command == 'list':
            if args.active:
                sessions = manager.get_active_sessions()
            elif args.status:
                status = SessionStatus(args.status)
                sessions = manager.get_sessions_by_status(status)
            elif args.lecturer:
                sessions = manager.get_sessions_by_lecturer(args.lecturer)
            else:
                sessions = manager.read_sessions()
            
            if sessions:
                print(f"\nFound {len(sessions)} sessions:")
                print("-" * 80)
                print(f"{'ID':<20} {'Course':<8} {'Faculty':<6} {'Sec':<3} {'Day':<10} {'Status':<12} {'Room':<8}")
                print("-" * 80)
                for session in sessions:
                    print(f"{session['Session_ID']:<20} {session['Course']:<8} {session['Faculty']:<6} "
                          f"{session['Section']:<3} {session['Day']:<10} {session['Status']:<12} {session['Room']:<8}")
            else:
                print("No sessions found.")
        
        elif args.command == 'update':
            status = SessionStatus(args.status)
            notes = args.notes or ""
            success = manager.update_session_status(args.session_id, status, notes)
            if success:
                print(f"✓ Session {args.session_id} updated to {args.status}")
            else:
                print(f"✗ Failed to update session {args.session_id}")
        
        elif args.command == 'report':
            report = manager.generate_daily_report(args.date)
            print(f"\n📊 Daily Report for {report['date']}")
            print("=" * 40)
            print(f"Total Sessions: {report['total_sessions']}")
            
            if report['by_status']:
                print("\nBy Status:")
                for status, count in report['by_status'].items():
                    print(f"  {status}: {count}")
            
            if report['by_lecturer']:
                print("\nBy Lecturer:")
                for lecturer, count in report['by_lecturer'].items():
                    print(f"  {lecturer}: {count}")
    
    except Exception as e:
        print(f"✗ Error: {e}")
        sys.exit(1)

if __name__ == '__main__':
    main()
