CREATE TABLE IF NOT EXISTS b_ml_model
(
	ID int not null auto_increment,
	NAME varchar(60) not null,
	TYPE varchar(16) not null,
	VERSION int null,
	STATE varchar(15),

	PRIMARY KEY(ID)
);
