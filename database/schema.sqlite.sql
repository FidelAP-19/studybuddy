-- StudyBuddy schema for the zero-setup SQLite demo mode.
-- Same relations and constraints as schema.mysql.sql; only the
-- dialect differs (AUTOINCREMENT, no CREATE DATABASE).
-- The app runs this file automatically the first time it starts in SQLite mode.

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS COURSE (
  Course_code  VARCHAR(10)  NOT NULL PRIMARY KEY,
  Course_name  VARCHAR(80)  NOT NULL,
  Department   VARCHAR(40)  NOT NULL
);

CREATE TABLE IF NOT EXISTS STUDENT (
  Student_id   INTEGER      PRIMARY KEY AUTOINCREMENT,
  Fname        VARCHAR(40)  NOT NULL,
  Lname        VARCHAR(40)  NOT NULL,
  Email        VARCHAR(100) NOT NULL UNIQUE,
  Major        VARCHAR(60),
  Year_level   VARCHAR(10)  NOT NULL
               CHECK (Year_level IN ('Freshman','Sophomore','Junior','Senior','Graduate')),
  Created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS TUTOR_REQUEST (
  Request_id    INTEGER       PRIMARY KEY AUTOINCREMENT,
  Student_id    INTEGER       NOT NULL REFERENCES STUDENT(Student_id)
                              ON DELETE CASCADE ON UPDATE CASCADE,
  Course_code   VARCHAR(10)   NOT NULL REFERENCES COURSE(Course_code)
                              ON DELETE RESTRICT ON UPDATE CASCADE,
  Topic         VARCHAR(120)  NOT NULL,
  Description   TEXT          NOT NULL,
  Urgency       VARCHAR(6)    NOT NULL DEFAULT 'Medium'
                CHECK (Urgency IN ('Low','Medium','High')),
  Meeting_mode  VARCHAR(10)   NOT NULL DEFAULT 'Either'
                CHECK (Meeting_mode IN ('In person','Online','Either')),
  Availability  VARCHAR(60)   NOT NULL,
  Status        VARCHAR(8)    NOT NULL DEFAULT 'Open'
                CHECK (Status IN ('Open','Matched','Closed')),
  Submitted_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_request_course_status ON TUTOR_REQUEST (Course_code, Status);

CREATE VIEW IF NOT EXISTS COURSE_DEMAND AS
SELECT C.Course_code,
       C.Course_name,
       COUNT(R.Request_id)                                    AS Total_requests,
       SUM(CASE WHEN R.Status  = 'Open' THEN 1 ELSE 0 END)    AS Open_requests,
       SUM(CASE WHEN R.Urgency = 'High' THEN 1 ELSE 0 END)    AS High_urgency
FROM COURSE C LEFT JOIN TUTOR_REQUEST R ON C.Course_code = R.Course_code
GROUP BY C.Course_code, C.Course_name;

INSERT OR IGNORE INTO COURSE (Course_code, Course_name, Department) VALUES
 ('CIS344', 'Database Management Systems & Web Programming', 'Computer Information Systems'),
 ('CIS345', 'Introduction to Systems Analysis',              'Computer Information Systems'),
 ('CSC101', 'Principles of Information & Computation',       'Computer Science'),
 ('CSC211', 'Advanced Programming Techniques',               'Computer Science'),
 ('MAT141', 'Precalculus',                                   'Mathematics'),
 ('MAT301', 'Discrete Mathematics',                          'Mathematics'),
 ('STA250', 'Principles of Statistics',                      'Mathematics'),
 ('ENG101', 'Composition I',                                 'English');
