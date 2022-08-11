import * as dotenv from "dotenv";

dotenv.config();

/*
 * Based on an idea from Austin Shelby.
 * Reference: https://www.austinshelby.com/blog/the-correct-way-to-load-environment-variables-in-nextjs
 */
const getEnvironmentVariable = (environmentVariable: string): string => {
  const unvalidatedEnvironmentVariable = process.env[environmentVariable];

  if (!unvalidatedEnvironmentVariable) {
    throw new Error(`Couldn't find environment variable: ${environmentVariable}`);
  }

  return unvalidatedEnvironmentVariable;
};

export const config = {
  CONTRACT_ADDRESS: getEnvironmentVariable("CONTRACT_ADDRESS"),
  RPC_ENDPOINT: getEnvironmentVariable("RPC_ENDPOINT"),
  S3_ACCESS_KEY: getEnvironmentVariable("S3_ACCESS_KEY"),
  S3_SECRET_KEY: getEnvironmentVariable("S3_SECRET_KEY"),
  S3_ENDPOINT_URL: getEnvironmentVariable("S3_ENDPOINT_URL"),
  S3_BUCKET_NAME: getEnvironmentVariable("S3_BUCKET_NAME"),
  S3_PATH_PREFIX: getEnvironmentVariable("S3_PATH_PREFIX"),
  START_TOKEN_ID: getEnvironmentVariable("START_TOKEN_ID"),
  PRIVATE_ASSETS_PATH: getEnvironmentVariable("PRIVATE_ASSETS_PATH"),
  PUBLIC_ASSETS_PATH: getEnvironmentVariable("PUBLIC_ASSETS_PATH"),
  ASSETS_EXTENSION: getEnvironmentVariable("ASSETS_EXTENSION"),
  PRIVATE_METADATA_PATH: getEnvironmentVariable("PRIVATE_METADATA_PATH"),
  PUBLIC_METADATA_PATH: getEnvironmentVariable("PUBLIC_METADATA_PATH"),
  PUBLIC_ASSETS_URI_TEMPLATE: getEnvironmentVariable("PUBLIC_ASSETS_URI_TEMPLATE"),
  FULL_REFRESH_DELAY: getEnvironmentVariable("FULL_REFRESH_DELAY"),
};
