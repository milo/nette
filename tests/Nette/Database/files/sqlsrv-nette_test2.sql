IF OBJECT_ID('note', 'U') IS NOT NULL DROP TABLE note;
IF OBJECT_ID('book_tag_alt', 'U') IS NOT NULL DROP TABLE book_tag_alt;
IF OBJECT_ID('book_tag', 'U') IS NOT NULL DROP TABLE book_tag;
IF OBJECT_ID('book', 'U') IS NOT NULL DROP TABLE book;
IF OBJECT_ID('tag', 'U') IS NOT NULL DROP TABLE tag;
IF OBJECT_ID('author', 'U') IS NOT NULL DROP TABLE author;

IF OBJECT_ID('nUsers_nTopics_alt', 'U') IS NOT NULL DROP TABLE [nUsers_nTopics_alt];
IF OBJECT_ID('nUsers_nTopics', 'U') IS NOT NULL DROP TABLE [nUsers_nTopics];
IF OBJECT_ID('nTopics', 'U') IS NOT NULL DROP TABLE [nTopics];
IF OBJECT_ID('nPriorities', 'U') IS NOT NULL DROP TABLE [nPriorities];
IF OBJECT_ID('nUsers', 'U') IS NOT NULL DROP TABLE [nUsers];

IF OBJECT_ID('types', 'U') IS NOT NULL DROP TABLE types;
IF OBJECT_ID('types2', 'U') IS NOT NULL DROP TABLE types2;



CREATE TABLE [nUsers] (
	[nUserId] int NOT NULL IDENTITY(1,1),
	[name] varchar(100) NOT NULL,
	PRIMARY KEY ([nUserId])
);

INSERT INTO [nUsers] ([name]) VALUES
('John'),
('Doe');



CREATE TABLE [nPriorities] (
	[nPriorityId] int NOT NULL IDENTITY(20,1),
	[name] varchar(100) NOT NULL,
	PRIMARY KEY ([nPriorityId])
);

INSERT INTO [nPriorities] ([name]) VALUES
('High'),
('Medium'),
('Low');



CREATE TABLE [nTopics] (
	[nTopicId] int NOT NULL IDENTITY(10,1),
	[title] varchar(100) NOT NULL,
	[nPriorityId] int NOT NULL,
	PRIMARY KEY ([nTopicId]),
	CONSTRAINT priority_id FOREIGN KEY (nPriorityId) REFERENCES nPriorities (nPriorityId)
);

INSERT INTO [nTopics] ([title], [nPriorityId]) VALUES
('Topic #1', 20),
('Topic #2', 20),
('Topic #3', 22);



CREATE TABLE [nUsers_nTopics] (
	[nUserId] int NOT NULL,
	[nTopicId] int NOT NULL,
	PRIMARY KEY ([nUserId], [nTopicId]),
	CONSTRAINT user_id FOREIGN KEY (nUserId) REFERENCES nUsers (nUserId),
	CONSTRAINT topic_id FOREIGN KEY (nTopicId) REFERENCES nTopics (nTopicId)
);

INSERT INTO [nUsers_nTopics] ([nUserId], [nTopicId]) VALUES
(1, 10),
(1, 12),
(2, 11);



CREATE TABLE [nUsers_nTopics_alt] (
	[nUserId] int NOT NULL,
	[nTopicId] int NOT NULL,
	PRIMARY KEY ([nUserId], [nTopicId]),
	CONSTRAINT user_id_alt FOREIGN KEY (nUserId) REFERENCES nUsers (nUserId),
	CONSTRAINT topic_id_alt FOREIGN KEY (nTopicId) REFERENCES nTopics (nTopicId)
);

INSERT INTO [nUsers_nTopics_alt] ([nUserId], [nTopicId]) VALUES
(2, 10);
