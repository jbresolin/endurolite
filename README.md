# EnduroLite

This a **partial** version of https://github.com/jbresolin/enduro . Unlike its big brother, it is functional, albeit with limited features.

Instead of a _manager_ screen where information from multiple _manual lap counters_ is compiled, this uses a single manual counter and displays a totals table to the manager. The totals page also includes a script to update the results website (see https://github.com/tiagomartines11/baja-sae-brasil-online ).

The db.sql file is a dump of the MySQL schema being used and includes sample data from 2 events. The tables _users, evento and equipe_ are temporary, since that information should be pulled from the parent application's database once the merger is made. The database includes sample data from two events in which this application has been successfully used.

Uses Slim and Bootstrap.

