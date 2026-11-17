-- =============================================================
-- StudyBuddy: Peer Tutoring Request Platform
-- MySQL / MariaDB schema  (run once: mysql -u root -p < database/schema.mysql.sql)
-- Table and attribute naming follows the Elmasri & Navathe
-- COMPANY-schema style (UPPERCASE relations, Capitalized attributes).
-- =============================================================

CREATE DATABASE IF NOT EXISTS studybuddy
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studybuddy;

DROP VIEW  IF EXISTS COURSE_DEMAND;
DROP TABLE IF EXISTS TUTOR_REQUEST;
DROP TABLE IF EXISTS STUDENT;
DROP TABLE IF EXISTS COURSE;

-- Courses that students can ask for help with
CREATE TABLE COURSE (
  Course_code  VARCHAR(10)  NOT NULL,
  Course_name  VARCHAR(80)  NOT NULL,
  Department   VARCHAR(40)  NOT NULL,
  PRIMARY KEY (Course_code)
);

-- One row per student (identified by e-mail; re-submitting updates the row)
CREATE TABLE STUDENT (
  Student_id   INT          NOT NULL AUTO_INCREMENT,
  Fname        VARCHAR(40)  NOT NULL,
  Lname        VARCHAR(40)  NOT NULL,
  Email        VARCHAR(100) NOT NULL,
  Major        VARCHAR(60),
  Year_level   VARCHAR(10)  NOT NULL,
  Created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Student_id),
  UNIQUE (Email),
  CHECK (Year_level IN ('Freshman','Sophomore','Junior','Senior','Graduate'))
);

-- Every form submission becomes one TUTOR_REQUEST row
CREATE TABLE TUTOR_REQUEST (
  Request_id    INT           NOT NULL AUTO_INCREMENT,
  Student_id    INT           NOT NULL,
  Course_code   VARCHAR(10)   NOT NULL,
  Topic         VARCHAR(120)  NOT NULL,
  Description   TEXT          NOT NULL,
  Urgency       VARCHAR(6)    NOT NULL DEFAULT 'Medium',
  Meeting_mode  VARCHAR(10)   NOT NULL DEFAULT 'Either',
  Availability  VARCHAR(60)   NOT NULL,
  Status        VARCHAR(8)    NOT NULL DEFAULT 'Open',
  Submitted_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Request_id),
  FOREIGN KEY (Student_id)  REFERENCES STUDENT(Student_id)
      ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (Course_code) REFERENCES COURSE(Course_code)
      ON DELETE RESTRICT ON UPDATE CASCADE,
  CHECK (Urgency      IN ('Low','Medium','High')),
  CHECK (Meeting_mode IN ('In person','Online','Either')),
  CHECK (Status       IN ('Open','Matched','Closed'))
);

-- Speeds up the Request Board filter (course + status)
CREATE INDEX idx_request_course_status ON TUTOR_REQUEST (Course_code, Status);

-- Demand per course, used by the Stats page
CREATE VIEW COURSE_DEMAND AS
SELECT C.Course_code,
       C.Course_name,
       COUNT(R.Request_id)                                    AS Total_requests,
       SUM(CASE WHEN R.Status  = 'Open' THEN 1 ELSE 0 END)    AS Open_requests,
       SUM(CASE WHEN R.Urgency = 'High' THEN 1 ELSE 0 END)    AS High_urgency
FROM COURSE C LEFT JOIN TUTOR_REQUEST R ON C.Course_code = R.Course_code
GROUP BY C.Course_code, C.Course_name;

-- Starter course list (edit freely)
INSERT INTO COURSE (Course_code, Course_name, Department) VALUES
 ('CIS344', 'Database Management Systems & Web Programming', 'Computer Information Systems'),
 ('CIS345', 'Introduction to Systems Analysis',              'Computer Information Systems'),
 ('CSC101', 'Principles of Information & Computation',       'Computer Science'),
 ('CSC211', 'Advanced Programming Techniques',               'Computer Science'),
 ('MAT141', 'Precalculus',                                   'Mathematics'),
 ('MAT301', 'Discrete Mathematics',                          'Mathematics'),
 ('STA250', 'Principles of Statistics',                      'Mathematics'),
 ('ENG101', 'Composition I',                                 'English');
