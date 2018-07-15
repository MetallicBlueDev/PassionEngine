# Contributing to TR ENGINE
Any contribution is welcome and valued. 

# Reporting bugs
To report a bug, ensure you have a GitHub account. 
Search the issues page to see if the bug has already been reported. 
If not, create a new issue and write the steps to reproduce.

# Translation
There is a lot to do here. the major problem is that the translation scheme is not yet finished.
You have to start from the French translation to go to the other languages.

# Contributing code
## Steps
1. First, ensure you have a GitHub account and [fork](https://help.github.com/articles/fork-a-repo/) the repository.
2. Create a new branch from develop (unless you are contributing to another) and commit your changes to that.
3. Submit a new [pull request](https://help.github.com/articles/using-pull-requests/).
4. Wait for other users to test and review your changes.

## Credits
If you are contributing to TR Engine, please add your name to ```./contributors.md``` so that you can be credited for your work outside and inside the game.

## Language
Pure PHP with standard functions.

# Coding Style
Rather than writing hundreds of lines on the standards, try to just soak in what already exists.

## Use the standards
Readability is important, but not doing too much is even more important.

Use the *PHP Standards Recommendations* :
https://www.php-fig.org/psr/#accepted

## PSR respected
If possible, we will try to do as they do. In more simple.

### PSR-4: Autoloader 
(As of 2014-10-21 PSR-0 has been marked as deprecated. PSR-4 is now recommended as an alternative.)
https://www.php-fig.org/psr/psr-4/

### PSR-1: Basic Coding Standard
https://www.php-fig.org/psr/psr-1/

> **Note**
> 4.2. Properties : MUST be in lower case.

### PSR-2: Coding Style Guide
https://www.php-fig.org/psr/psr-2/

## Rule for the database
1. Put everything in lowercase
2. Separate words with the underscore
3. Put the names of the tables in the plural
4. Avoid abbreviations
5. Use the UTF-8 charset
6. Use SQL constraints (FOREIGN KEY, REFERENCES, ...)
