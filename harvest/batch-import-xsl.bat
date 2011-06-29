@echo off
rem Make sure that environment edits are local and that we have access to the 
rem Windows command extensions.
setlocal enableextensions
if not errorlevel 1 goto extensionsokay
echo Unable to enable Windows command extensions.
goto end
:extensionsokay

rem Make sure VUFIND_HOME is set:
if not "!%VUFIND_HOME%!"=="!!" goto vufindhomefound
rem VUFIND_HOME not set -- try to call vufind.bat to 
rem fix the problem before we give up completely
if exist %0\..\..\vufind.bat goto usevufindbat
rem If vufind.bat doesn't exist, the user hasn't run install.bat yet.
echo ERROR: vufind.bat does not exist -- could not set up environment.
echo Please run install.bat to correct this problem.
goto end
:usevufindbat
cd %0\..\..
call vufind > nul
cd %0\..
if not "!%VUFIND_HOME%!"=="!!" goto vufindhomefound
echo You need to set the VUFIND_HOME environmental variable before running this script.
goto end
:vufindhomefound

rem Make sure command line parameter was included:
if not "!%2!"=="!!" goto paramsokay
echo This script processes a batch of harvested XML records using the specified XSL
echo import configuration file.
echo.
echo Usage: %0 [%VUFIND_HOME%\harvest subdirectory] [properties file]
echo.
echo Note: Unless an absolute path is used, [properties file] is treated as being
echo       relative to %VUFIND_HOME%\import.
echo.
echo Example: %0 oai_source ojs.properties
goto end
:paramsokay

rem Check if the path is valid:
set BASEPATH="%VUFIND_HOME%\harvest\%1"
if exist %BASEPATH% goto basepathfound
echo Directory %BASEPATH% does not exist!
goto end
:basepathfound

rem Create log/processed directories as needed:
if exist %BASEPATH%\processed goto processedfound
md %BASEPATH%\processed
:processedfound

rem Flag -- do we need to perform an optimize?
set OPTIMIZE=0

rem Process all the files in the target directory:
cd %VUFIND_HOME%\import
for %%a in (%BASEPATH%\*.xml) do (
  echo Processing %%a...
  php import-xsl.php %%a %2
  rem Unfortunately, PHP doesn't seem to set apropriate errorlevels, so error
  rem detection doesn't work under Windows like it does under Linux... however,
  rem this code is retained in case PHP's behavior improves in the future!
  if errorlevel 0 (
    move %%a %BASEPATH%\processed\ > nul
    rem We processed a file, so we need to optimize later on:
    set OPTIMIZE=1
  )
)

rem Optimize the index now that we are done (if necessary):
if not "%OPTIMIZE%!"=="1!" goto end
cd %VUFIND_HOME%\util
echo Optimizing index...
php optimize.php

:end