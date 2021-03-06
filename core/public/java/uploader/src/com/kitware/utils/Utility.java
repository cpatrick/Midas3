package com.kitware.utils;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.PrintStream;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.text.DecimalFormat;

import com.kitware.utils.exception.JavaUploaderException;
import com.kitware.utils.exception.JavaUploaderHttpServerErrorException;
import com.kitware.utils.exception.JavaUploaderNetworkException;
import com.kitware.utils.exception.JavaUploaderQueryHttpServerException;

public class Utility
{
  public static final String NEWLINE = "\r\n";
  public static final String HTTP_SERVER_ERROR_ANSWER_PREFIX = "[ERROR]"; 
  public static final String HTTP_SERVER_CORRECT_ANSWER_PREFIX = "[OK]";
  
  public static LOG_LEVEL EFFECTIVE_LOG_LEVEL = LOG_LEVEL.WARNING; 
  public static enum LOG_LEVEL { FATAL, ERROR, WARNING, DEBUG, LOG };
  
  public static URL buildURL(String name, String url) throws JavaUploaderException
    {
    try
      {
      return new URL(url); 
      }
    catch (MalformedURLException e)
      {
      throw new JavaUploaderException("Malformed "+name+" URL:"+url, e); 
      }
    catch(java.lang.NullPointerException e)
      {
      // Do something here for the missing destination
      throw new JavaUploaderException(name+" URL not specified", e);
      }
    }
  
  public static String queryHttpServer(String queryURL) throws JavaUploaderQueryHttpServerException
    {
    HttpURLConnection conn = null; 
    try
      {
        URL url = new URL(queryURL);
        conn = (HttpURLConnection) url.openConnection();
        conn.setDoInput(true); //Allow Inputs
        conn.setDoOutput(false); // Allow Outputs
        conn.setUseCaches(false); // Don't use a cached copy.
        conn.setRequestMethod("GET"); // Use a PUT method.
        conn.setRequestProperty("Connection", "close");
        conn.setRequestProperty("Host", url.getHost());
        conn.setRequestProperty("Accept", "text/plain"); 
        
        BufferedReader in = new BufferedReader(
            new InputStreamReader(conn.getInputStream()));
        try
          {
          return Utility.getMessage(in);
          }
        catch (JavaUploaderNetworkException e)
          {
          throw new JavaUploaderQueryHttpServerException("Query using '"+queryURL+"' returns malformed answer", e);
          }
        finally
        {
        if (conn!=null) { conn.disconnect(); }
        }
      }
    catch (MalformedURLException e)
      {
      throw new JavaUploaderQueryHttpServerException("Malformed queryURL:"+queryURL);
      }
    catch (IOException e)
      {
      throw new JavaUploaderQueryHttpServerException("Failed to connect to server using '"+queryURL+"'");
      }
    }
  
  public static String getMessage(BufferedReader in) throws JavaUploaderNetworkException, JavaUploaderHttpServerErrorException
    {
    String msg = "", output = "";
    boolean isMultilineCorrectAnswer = false;
    boolean isWrongAnswer = false;
    try
      {
      
      while ((msg = in.readLine()) != null)
        {
        if (msg.startsWith(HTTP_SERVER_ERROR_ANSWER_PREFIX))
          {
          String error = msg.substring(HTTP_SERVER_ERROR_ANSWER_PREFIX.length());
          throw new JavaUploaderHttpServerErrorException(error); 
          }
        else if (msg.startsWith(HTTP_SERVER_CORRECT_ANSWER_PREFIX))
          {
          output = msg.substring(HTTP_SERVER_CORRECT_ANSWER_PREFIX.length());
          isMultilineCorrectAnswer = true;
          }
        else if (isMultilineCorrectAnswer)
          {
          output += ( msg + NEWLINE );
          }
        else 
          {
          if (!isWrongAnswer) {isWrongAnswer = true;}
          else 
            {
            output += ( msg + NEWLINE );
            }
          }
        }
      if (isWrongAnswer && !isMultilineCorrectAnswer)
        {
        Utility.log(Utility.LOG_LEVEL.ERROR, "Malformed answer:"+output); 
        throw new JavaUploaderNetworkException("Malformed answer:"+output);
        }
      return output; 
      }
    catch (IOException e)
      {
      throw new JavaUploaderNetworkException("Failed to read data from server:"+msg, e);
      }
    }
  
  public static void log(LOG_LEVEL level, Throwable e)
    {
    Utility.log(level, "", e); 
    }
  
  public static void log(LOG_LEVEL level, String message)
    {
    Utility.log(level, message, null); 
    }
  
  public static void log(LOG_LEVEL level, String message, Throwable e)
    {
    if (Utility.EFFECTIVE_LOG_LEVEL.ordinal() < level.ordinal()) return; 
    PrintStream printStream = System.out; 
    if (level.ordinal() <= LOG_LEVEL.WARNING.ordinal())
      {
      printStream = System.err; 
      }
    
    String logMsg = "["+level+"]";
    logMsg += message.equals("") ? "" : message; 
    if (e!=null)
      {
      logMsg += " - Exception: " + e.getClass().getName() + (e.getMessage()==null?"":(" - " + e.getMessage())); 
      }
    printStream.println(logMsg); 
   
    if (e != null)
      {
      e.printStackTrace();
      }
    }

  /**
   * Convert a number of bytes into a human readable string
   * @param bytes Numeric bytes (ex: 123899381)
   * @return Human readable version of bytes (ex: 1.4 GB)
   */
  public static String bytesToString(long bytes)
    {
    if(bytes > 1024 * 1024 * 1024)
      {
      return new DecimalFormat("#.##").format((double)bytes / (1024.0 * 1024.0 * 1024.0)) + " GB";
      }
    if(bytes > 1024 * 1024)
      {
      return new DecimalFormat("#.##").format((double)bytes / (1024.0 * 1024.0)) + " MB";
      }
    if(bytes > 1024)
      {
      return new DecimalFormat("#.##").format((double)bytes / 1024.0) + " KB";
      }
    return bytes + " B";
    }
}
