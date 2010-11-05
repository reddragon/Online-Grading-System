<?php

#-------------------------------------------------------------------------------
# DESCRIPTION: This file contains all SQL queries to be used by the PHP scripts
#              The queries are stored in the global $cfg['sql'] associative
#              array.
#-------------------------------------------------------------------------------

$cfg['sql']['create']['users'] = <<<SQL
CREATE TABLE users
(
	user_id		INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	handle		VARCHAR(15) UNIQUE,
	password	VARCHAR(50),
	first_name	VARCHAR(50),
	last_name	VARCHAR(50),
	birth_date	DATE,
	address		VARCHAR(100),
	city		VARCHAR(50),
	state		VARCHAR(50),
	division	VARCHAR(50),
	zip			VARCHAR(10),
	phone		VARCHAR(20),
	email		VARCHAR(50),
	quote		VARCHAR(200),
	theme		VARCHAR(20) DEFAULT 'default',
	question	VARCHAR(100),
	secret		VARCHAR(100),
	registered	TIMESTAMP,
	rating		FLOAT DEFAULT 300,
	volatility	FLOAT DEFAULT 50
);
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['results'] = <<<SQL
CREATE TABLE results
(
    user_id		INT NOT NULL REFERENCES users(user_id),
    contest_id  INT NOT NULL REFERENCES contests(contest_id),
    rating      FLOAT NOT NULL,
    volatility  FLOAT NOT NULL,
    old_rat     FLOAT NOT NULL,
    old_vol     FLOAT NOT NULL,
    PRIMARY KEY(user_id, contest_id)
);
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['groups'] = <<<SQL
CREATE TABLE groups
(
	group_id	INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	name			VARCHAR(50) UNIQUE
);
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['categories'] = <<<SQL
CREATE TABLE categories
(
	cat_id		INT PRIMARY KEY NOT NULL,
	name			VARCHAR(100)
);
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['problems'] = <<<SQL
CREATE TABLE problems
(
	prob_id		VARCHAR(10) NOT NULL,
	contest_id	INT NOT NULL REFERENCES contests(contest_id),
	cat_id		INT REFERENCES categories(cat_id),
	summary		VARCHAR(100),
	content		TEXT,
	weight		INT UNSIGNED,
	time_limit	FLOAT,
    mem_limit	FLOAT,
    version     INT DEFAULT 0,
	PRIMARY KEY(prob_id, contest_id)
);
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['contests'] = <<<SQL
CREATE TABLE contests
(
	contest_id	INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	name		VARCHAR(100),
	description	TEXT,
	team_size	INT,
	division	VARCHAR(50),
	show_time	DATETIME,
	begin_time	DATETIME,
    end_time	DATETIME,
    tested      BIT NOT NULL DEFAULT 0,
	manager		INT NOT NULL REFERENCES users(user_id),
	closed      BIT NOT NULL DEFAULT 0,
    rules       VARCHAR(100)
);
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['teams'] = <<<SQL
CREATE TABLE teams
(
	team_id		INT NOT NULL AUTO_INCREMENT,
	contest_id	INT NOT NULL REFERENCES contests(contest_id),
	name		VARCHAR(15),
	college		VARCHAR(100),
    score       FLOAT,
    last_seen   DATETIME,
	PRIMARY KEY(team_id, contest_id),
	UNIQUE(contest_id, name)
)
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['members'] = <<<SQL
CREATE TABLE members
(
	contest_id	INT NOT NULL REFERENCES contests(contest_id),
	team_id		INT NOT NULL REFERENCES teams(team_id), 
    user_id		INT NOT NULL REFERENCES users(user_id),
    old_rating  FLOAT,
    new_rating  FLOAT,
	PRIMARY KEY(contest_id, user_id)
)
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['solutions'] = <<<SQL
CREATE TABLE solutions
(
	team_id		INT NOT NULL REFERENCES teams(team_id),
	prob_id		VARCHAR(10) NOT NULL REFERENCES problems(prob_id),
	contest_id	INT NOT NULL REFERENCES contests(contest_id),
	language	VARCHAR(20),
	open_time	DATETIME,
	submit_time DATETIME,
	source		MEDIUMBLOB,
    submits		INT DEFAULT 0,
    score       FLOAT,
    passed      VARCHAR(100),
	PRIMARY KEY(contest_id, team_id, prob_id)
);
SQL;

#-------------------------------------------------------------------------------

# for autosaving solutions
$cfg['sql']['create']['drafts'] = <<<SQL
CREATE TABLE drafts
(
    user_id		INT NOT NULL REFERENCES users(user_id),
    prob_id		VARCHAR(10) NOT NULL REFERENCES problems(prob_id),
    contest_id	INT NOT NULL REFERENCES contests(contest_id),
    language	VARCHAR(20),
    source		MEDIUMBLOB,
    created     DATETIME NOT NULL,
    PRIMARY KEY(user_id)
);
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['perms'] = <<<SQL
CREATE TABLE perms
(
	user_id		INT NOT NULL REFERENCES users(user_id),
	group_id	INT NOT NULL REFERENCES groups(group_id),
	PRIMARY KEY(user_id, group_id)
);
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['views'] = <<<SQL
CREATE TABLE views
(
	group_id	INT NOT NULL REFERENCES groups(group_id),
	view		VARCHAR(50) NOT NULL,
	PRIMARY KEY(group_id, view)
);
SQL;

#-------------------------------------------------------------------------------

$cfg['sql']['create']['forgot'] = <<<SQL
CREATE TABLE forgot
(
	user_id		INT NOT NULL REFERENCES users(user_id),
	password	VARCHAR(50),
	PRIMARY KEY(user_id)
);
SQL;

#-------------------------------------------------------------------------------

# table for holding submitted programs temporarily while they're being tested
$cfg['sql']['create']['submits'] = <<<SQL
CREATE TABLE submits
(
	user_id		INT NOT NULL REFERENCES users(user_id),
	submit_id	INT NOT NULL AUTO_INCREMENT,
	prob_id		VARCHAR(10) NOT NULL REFERENCES problems(prob_id),
	contest_id	INT NOT NULL REFERENCES contests(contest_id),
	source		MEDIUMBLOB,
	language		VARCHAR(20),
	time			DATETIME,
	mode			VARCHAR(20),
    custom      TEXT DEFAULT NULL,
	PRIMARY KEY(user_id, submit_id)
);
SQL;

#-------------------------------------------------------------------------------

# table for holding submitted programs temporarily while they're being tested
$cfg['sql']['create']['bulletin'] = <<<SQL
CREATE TABLE bulletin
(
    post_id     INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    poster_id	INT NOT NULL REFERENCES users(user_id),
    subject     VARCHAR(100),
    message     TEXT,
    level       INT NOT NULL,
    posted      DATETIME,
    addbreaks	BIT NOT NULL DEFAULT 1
);
SQL;


#-------------------------------------------------------------------------------
# Queries: users

$cfg['sql']['users_list'] = 'SELECT user_id, handle, first_name, last_name, email FROM users';
$cfg['sql']['user_by_handle'] = 'SELECT * FROM users WHERE handle = ?';
$cfg['sql']['user_by_id'] = 'SELECT * FROM users WHERE user_id = ?';
$cfg['sql']['user_by_email'] = 'SELECT * FROM users WHERE email = ?';
$cfg['sql']['clean_forgot'] = 'DELETE FROM forgot WHERE user_id = ?';
$cfg['sql']['del_user_by_id'] = 'DELETE FROM users WHERE user_id = ?';

#-------------------------------------------------------------------------------
# Queries: groups

$cfg['sql']['groups_list'] = 'SELECT * FROM groups';
$cfg['sql']['group_by_name'] = 'SELECT * FROM groups WHERE name = ?';
$cfg['sql']['group_by_id'] = 'SELECT * FROM groups WHERE group_id = ?';
$cfg['sql']['del_group_by_id'] = 'DELETE FROM groups WHERE group_id = ?';

#-------------------------------------------------------------------------------
# Queries: views and groups

$cfg['sql']['views_by_group_id'] = 'SELECT * FROM views WHERE group_id = ?';
$cfg['sql']['group_and_view'] = 'SELECT * FROM views WHERE group_id = ? AND view = ?';
$cfg['sql']['del_group_and_view'] = 'DELETE FROM views WHERE group_id = ? AND view = ?';

#-------------------------------------------------------------------------------
# Queries: users and groups

$cfg['sql']['groups_by_user_id'] = 'SELECT * FROM groups, perms' .
		' WHERE groups.group_id = perms.group_id AND user_id = ?';
$cfg['sql']['user_and_group_name'] = 'SELECT * FROM perms, groups WHERE user_id = ? AND groups.group_id = perms.group_id AND groups.name = ?';
$cfg['sql']['user_and_group'] = 'SELECT * FROM perms WHERE user_id = ? AND group_id = ?';
$cfg['sql']['del_user_and_group'] = 'DELETE FROM perms WHERE user_id = ? AND group_id = ?';
$cfg['sql']['users_by_group_name'] = 'SELECT * FROM groups, perms, users WHERE groups.name = ?'.
	' AND groups.group_id = perms.group_id AND perms.user_id = users.user_id';
 
$cfg['sql']['auth_check'] = 'SELECT view FROM views, groups, perms' .
		' WHERE views.group_id = groups.group_id AND perms.group_id = groups.group_id' .
		' AND perms.user_id = ?';
$cfg['sql']['del_user_perms_by_id'] = 'DELETE FROM perms WHERE user_id = ?';

#-------------------------------------------------------------------------------
# Queries: contests

$cfg['sql']['contests_list'] = 'SELECT * FROM contests';
$cfg['sql']['relevant_contests_list'] = 'SELECT *, begin_time < NOW() AS running FROM contests WHERE show_time < NOW() AND end_time > NOW()';
$cfg['sql']['running_contests_list'] = 'SELECT * FROM contests WHERE begin_time < NOW() AND end_time > NOW()';
$cfg['sql']['count_running_contests'] = 'SELECT COUNT(*) AS count FROM contests WHERE begin_time < NOW() AND end_time > NOW() ORDER BY end_time DESC';
$cfg['sql']['past_contests_list'] = 'SELECT * FROM contests WHERE end_time < NOW() ORDER BY end_time DESC';
$cfg['sql']['contest_by_id'] = 'SELECT *, show_time > NOW() AS show_future, begin_time > NOW() AS begin_future, end_time > NOW() AS end_future FROM contests WHERE contest_id = ?';
$cfg['sql']['contest_by_id_safe'] = 'SELECT *, begin_time > NOW() AS begin_future, end_time > NOW() AS end_future ' .
		'FROM contests WHERE contest_id = ? AND show_time < NOW()';
$cfg['sql']['del_contest_by_id'] = 'DELETE FROM contests WHERE contest_id = ?';
$cfg['sql']['contests_by_manager'] = 'SELECT * FROM contests WHERE manager = ?';
$cfg['sql']['contest_time'] = 'SELECT UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(begin_time) AS time, '.
	'UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP() AS remain FROM contests WHERE contest_id = ?';

#-------------------------------------------------------------------------------
# Queries: problems

$cfg['sql']['problems_by_contest_id'] = 'SELECT prob_id, cat_id, summary, weight, time_limit, mem_limit '.
	'FROM problems WHERE contest_id = ? ORDER BY weight ASC';
$cfg['sql']['problem_by_id'] = 'SELECT prob_id, contest_id, cat_id, summary, weight, time_limit, mem_limit, version FROM problems WHERE prob_id = ? AND contest_id = ?';    
$cfg['sql']['problem_content_by_id'] = 'SELECT content FROM problems WHERE prob_id = ? AND contest_id = ?';
$cfg['sql']['del_problem_by_id'] = 'DELETE FROM problems WHERE prob_id = ? AND contest_id = ?';

#-------------------------------------------------------------------------------
# Queries: teams

$cfg['sql']['team_by_name'] = 'SELECT * FROM teams WHERE name = ? AND contest_id = ?';
$cfg['sql']['count_teams_by_contest_id'] = 'SELECT COUNT(team_id) AS count FROM teams WHERE contest_id = ?';
$cfg['sql']['teams_by_contest_id'] = 'SELECT * FROM teams WHERE contest_id = ? ORDER BY score DESC';
$cfg['sql']['count_last_teams_by_contest_id'] = 'SELECT COUNT(team_id) AS count FROM teams '.
'WHERE contest_id = ? AND UNIX_TIMESTAMP(last_seen)+30*60 > UNIX_TIMESTAMP(NOW())';
$cfg['sql']['team_seen'] = 'UPDATE teams SET last_seen = NOW() WHERE contest_id = ? AND team_id = ?';
$cfg['sql']['standings_by_contest_id'] = 'SELECT teams.team_id, teams.name, teams.college, SUM(solutions.score) AS score FROM teams, solutions
WHERE teams.team_id=solutions.team_id AND solutions.contest_id=? AND teams.contest_id=solutions.contest_id
GROUP BY solutions.team_id ORDER BY score DESC';

#-------------------------------------------------------------------------------
# Queries: members

$cfg['sql']['users_by_contest_id'] = 'SELECT * FROM members, teams WHERE contest_id = ?';
$cfg['sql']['user_and_contest'] = 'SELECT * FROM members, teams '.
'WHERE user_id = ? AND members.contest_id = ? AND teams.contest_id=members.contest_id AND members.team_id = teams.team_id';
$cfg['sql']['users_by_team_id'] = 'SELECT handle FROM members, users WHERE members.user_id=users.user_id
AND members.contest_id=? AND members.team_id=?';
	
#-------------------------------------------------------------------------------
# Queries: solutions

$cfg['sql']['solution_by_id'] = 'SELECT *, UNIX_TIMESTAMP(submit_time)-UNIX_TIMESTAMP(open_time) AS elapsed, UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(open_time) AS elapsed2 '.
    'FROM solutions WHERE contest_id = ? AND team_id = ? AND prob_id = ?';
$cfg['sql']['score_by_id'] = 'SELECT score, language '.
    'FROM solutions WHERE contest_id = ? AND team_id = ? AND prob_id = ?';
$cfg['sql']['problems_by_team_id'] = 'SELECT prob_id FROM solutions WHERE team_id = ?';
$cfg['sql']['solutions_by_contest_id'] = 'SELECT teams.name, teams.last_seen, solutions.prob_id, weight, solutions.language, solutions.open_time, solutions.submit_time, solutions.submits, solutions.score, solutions.passed FROM solutions, teams, problems '.
	'WHERE solutions.contest_id = ? AND teams.contest_id=solutions.contest_id AND solutions.team_id = teams.team_id AND problems.prob_id = solutions.prob_id '.
	'ORDER BY weight DESC, submit_time DESC';
$cfg['sql']['open_solution'] = 'INSERT INTO solutions (contest_id, team_id, prob_id, open_time, submits) VALUES (?, ?, ?, NOW(), 0)';
$cfg['sql']['submit_solution'] = 'UPDATE solutions SET submits=submits+1, submit_time=NOW(), language=?, source=?, score=? '.
	'WHERE contest_id = ? AND team_id = ? AND prob_id = ?';
	
#-------------------------------------------------------------------------------
# Queries: submits

$cfg['sql']['submit_by_id'] = 'SELECT * FROM submits WHERE submit_id = ? AND user_id = ?';
$cfg['sql']['submit_by_user'] = 'SELECT * FROM submits WHERE user_id = ?';
$cfg['sql']['pending_submits'] = 'SELECT COUNT(*) AS count FROM submits';
$cfg['sql']['pending_user_submits'] = 'SELECT submit_id, time FROM submits WHERE user_id = ?';
$cfg['sql']['insert_submit'] = 'INSERT INTO submits (contest_id, prob_id, user_id, source, language, mode, time) '.
	'values (?, ?, ?, ?, ?, ?, NOW())';
$cfg['sql']['delete_submit'] = 'DELETE FROM submits WHERE submit_id = ? AND user_id = ?';
$cfg['sql']['expire_submits'] = 'DELETE FROM submits WHERE UNIX_TIMESTAMP(time)+? < UNIX_TIMESTAMP(NOW())';

#-------------------------------------------------------------------------------
# Queries: drafts

$cfg['sql']['draft_by_user'] = 'SELECT * FROM drafts WHERE user_id = ?';
$cfg['sql']['delete_draft_by_user'] = 'DELETE FROM drafts WHERE user_id = ?';
$cfg['sql']['update_draft'] = 'UPDATE drafts SET created = NOW() WHERE user_id = ?';
$cfg['sql']['clean_drafts'] = 'DELETE FROM drafts WHERE UNIX_TIMESTAMP(created)+'.
    $cfg['draft']['expiry_time'].' < UNIX_TIMESTAMP(NOW())';

#-------------------------------------------------------------------------------
# Queries: bulletins

$cfg['sql']['bulletin_by_level'] = 'SELECT bulletin.*, users.handle FROM bulletin, users 
WHERE level = ? AND users.user_id = bulletin.poster_id ORDER BY posted DESC';
$cfg['sql']['bulletin_post'] = 'INSERT INTO bulletin (poster_id, subject, message, posted, level, addbreaks)
VALUES (?, ?, ?, NOW(), ?, ?)';
$cfg['sql']['bulletin_by_id'] = 'SELECT * FROM bulletin WHERE post_id = ?';
$cfg['sql']['delete_bulletin_by_id'] = 'DELETE FROM bulletin WHERE post_id = ?';

?>
