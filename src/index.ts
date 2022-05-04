import * as dotenv from 'dotenv';
import {
  CollectionDataUpdater,
  ERC721Contract,
  ERC721CollectionStatusProvider,
  S3BasicFileDataUpdater,
  S3BasicNftMetadataDataUpdater,
  S3ConfigurationInterface,
  UpdateAllTokensEveryNSecondsRuntime,
  UpdateTokenOnMintRuntime,
} from '@hashlips-lab/collection-data-updater';

dotenv.config();

const contract = new ERC721Contract(
  process.env.CONTRACT_ADDRESS,
  process.env.RPC_ENDPOINT,
);

const s3Config = {
  accessKey: process.env.S3_ACCESS_KEY,
  secretKey: process.env.S3_SECRET_KEY,
  endpoint: process.env.S3_ENDPOINT_URL,
  bucketName: process.env.S3_BUCKET_NAME,
  pathPrefix: process.env.S3_PATH_PREFIX,
} as S3ConfigurationInterface;

const collectionDataUpdater = new CollectionDataUpdater(
  /*
   * This object tells which tokens can be revealed and which ones cannot.
   */
  new ERC721CollectionStatusProvider(contract),
  /*
   * The DataUpdaters are objects which perform operations whenever a token has to
   * be revealed or hidden.
   * The S3BasicFileDataUpdater is the simplest one: it copies a single file from
   * a private folder to a public one.
   */
  [
    // Order is respected, so you should update your assets before the metadata.
    new S3BasicFileDataUpdater(
      'Asset',
      s3Config,
      '/private/assets',
      '/public/assets',
      process.env.ASSETS_EXTENSION,
    ),
    new S3BasicNftMetadataDataUpdater(
      'Metadata',
      s3Config,
      '/private/metadata',
      '/public/metadata',
      (tokenId: number, metadata: any) => {
        // Update any metadata value here...
        metadata['image'] = process.env.PUBLIC_ASSETS_URI_TEMPLATE.replace('{{TOKEN_ID}}', tokenId.toString());

        return metadata;
      },
    ),
  ],
  /*
   * Runtimes are the objects which trigger updates on the data.
   * The CollectionDataUpdater offers methods to update a single token or the
   * whole collection, runtimes can call these methods in response to external
   * events or timers.
   */
  [
    new UpdateAllTokensEveryNSecondsRuntime(60 * 10),
    new UpdateTokenOnMintRuntime(contract),
  ],
);

collectionDataUpdater.start();
