<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit55be0a7fc0c96572f2f4290e4d3fbdf4
{
    public static $files = array (
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'k' => 
        array (
            'kornrunner\\Ethereum\\' => 20,
            'kornrunner\\' => 11,
        ),
        'W' => 
        array (
            'Web3p\\RLP\\' => 10,
        ),
        'S' => 
        array (
            'Symfony\\Polyfill\\Mbstring\\' => 26,
        ),
        'M' => 
        array (
            'Mdanter\\Ecc\\' => 12,
        ),
        'F' => 
        array (
            'FG\\' => 3,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'kornrunner\\Ethereum\\' => 
        array (
            0 => __DIR__ . '/..' . '/kornrunner/ethereum-address/src',
        ),
        'kornrunner\\' => 
        array (
            0 => __DIR__ . '/..' . '/kornrunner/ethereum-offline-raw-tx/src',
            1 => __DIR__ . '/..' . '/kornrunner/keccak/src',
            2 => __DIR__ . '/..' . '/kornrunner/secp256k1/src',
        ),
        'Web3p\\RLP\\' => 
        array (
            0 => __DIR__ . '/..' . '/web3p/rlp/src',
        ),
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Mdanter\\Ecc\\' => 
        array (
            0 => __DIR__ . '/..' . '/mdanter/ecc/src',
        ),
        'FG\\' => 
        array (
            0 => __DIR__ . '/..' . '/fgrosse/phpasn1/lib',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'FG\\ASN1\\ASNObject' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/ASNObject.php',
        'FG\\ASN1\\AbstractString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/AbstractString.php',
        'FG\\ASN1\\AbstractTime' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/AbstractTime.php',
        'FG\\ASN1\\Base128' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Base128.php',
        'FG\\ASN1\\Composite\\AttributeTypeAndValue' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Composite/AttributeTypeAndValue.php',
        'FG\\ASN1\\Composite\\RDNString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Composite/RDNString.php',
        'FG\\ASN1\\Composite\\RelativeDistinguishedName' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Composite/RelativeDistinguishedName.php',
        'FG\\ASN1\\Construct' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Construct.php',
        'FG\\ASN1\\Exception\\NotImplementedException' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Exception/NotImplementedException.php',
        'FG\\ASN1\\Exception\\ParserException' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Exception/ParserException.php',
        'FG\\ASN1\\ExplicitlyTaggedObject' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/ExplicitlyTaggedObject.php',
        'FG\\ASN1\\Identifier' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Identifier.php',
        'FG\\ASN1\\OID' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/OID.php',
        'FG\\ASN1\\Parsable' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Parsable.php',
        'FG\\ASN1\\TemplateParser' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/TemplateParser.php',
        'FG\\ASN1\\Universal\\BMPString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/BMPString.php',
        'FG\\ASN1\\Universal\\BitString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/BitString.php',
        'FG\\ASN1\\Universal\\Boolean' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/Boolean.php',
        'FG\\ASN1\\Universal\\CharacterString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/CharacterString.php',
        'FG\\ASN1\\Universal\\Enumerated' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/Enumerated.php',
        'FG\\ASN1\\Universal\\GeneralString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/GeneralString.php',
        'FG\\ASN1\\Universal\\GeneralizedTime' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/GeneralizedTime.php',
        'FG\\ASN1\\Universal\\GraphicString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/GraphicString.php',
        'FG\\ASN1\\Universal\\IA5String' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/IA5String.php',
        'FG\\ASN1\\Universal\\Integer' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/Integer.php',
        'FG\\ASN1\\Universal\\NullObject' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/NullObject.php',
        'FG\\ASN1\\Universal\\NumericString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/NumericString.php',
        'FG\\ASN1\\Universal\\ObjectDescriptor' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/ObjectDescriptor.php',
        'FG\\ASN1\\Universal\\ObjectIdentifier' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/ObjectIdentifier.php',
        'FG\\ASN1\\Universal\\OctetString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/OctetString.php',
        'FG\\ASN1\\Universal\\PrintableString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/PrintableString.php',
        'FG\\ASN1\\Universal\\RelativeObjectIdentifier' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/RelativeObjectIdentifier.php',
        'FG\\ASN1\\Universal\\Sequence' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/Sequence.php',
        'FG\\ASN1\\Universal\\Set' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/Set.php',
        'FG\\ASN1\\Universal\\T61String' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/T61String.php',
        'FG\\ASN1\\Universal\\UTCTime' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/UTCTime.php',
        'FG\\ASN1\\Universal\\UTF8String' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/UTF8String.php',
        'FG\\ASN1\\Universal\\UniversalString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/UniversalString.php',
        'FG\\ASN1\\Universal\\VisibleString' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/Universal/VisibleString.php',
        'FG\\ASN1\\UnknownConstructedObject' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/UnknownConstructedObject.php',
        'FG\\ASN1\\UnknownObject' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/ASN1/UnknownObject.php',
        'FG\\Utility\\BigInteger' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/Utility/BigInteger.php',
        'FG\\Utility\\BigIntegerBcmath' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/Utility/BigIntegerBcmath.php',
        'FG\\Utility\\BigIntegerGmp' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/Utility/BigIntegerGmp.php',
        'FG\\X509\\AlgorithmIdentifier' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/X509/AlgorithmIdentifier.php',
        'FG\\X509\\CSR\\Attributes' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/X509/CSR/Attributes.php',
        'FG\\X509\\CSR\\CSR' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/X509/CSR/CSR.php',
        'FG\\X509\\CertificateExtensions' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/X509/CertificateExtensions.php',
        'FG\\X509\\CertificateSubject' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/X509/CertificateSubject.php',
        'FG\\X509\\PrivateKey' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/X509/PrivateKey.php',
        'FG\\X509\\PublicKey' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/X509/PublicKey.php',
        'FG\\X509\\SAN\\DNSName' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/X509/SAN/DNSName.php',
        'FG\\X509\\SAN\\IPAddress' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/X509/SAN/IPAddress.php',
        'FG\\X509\\SAN\\SubjectAlternativeNames' => __DIR__ . '/..' . '/fgrosse/phpasn1/lib/X509/SAN/SubjectAlternativeNames.php',
        'Mdanter\\Ecc\\Crypto\\EcDH\\EcDH' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/EcDH/EcDH.php',
        'Mdanter\\Ecc\\Crypto\\EcDH\\EcDHInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/EcDH/EcDHInterface.php',
        'Mdanter\\Ecc\\Crypto\\Key\\PrivateKey' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/Key/PrivateKey.php',
        'Mdanter\\Ecc\\Crypto\\Key\\PrivateKeyInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/Key/PrivateKeyInterface.php',
        'Mdanter\\Ecc\\Crypto\\Key\\PublicKey' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/Key/PublicKey.php',
        'Mdanter\\Ecc\\Crypto\\Key\\PublicKeyInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/Key/PublicKeyInterface.php',
        'Mdanter\\Ecc\\Crypto\\Signature\\HasherInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/Signature/HasherInterface.php',
        'Mdanter\\Ecc\\Crypto\\Signature\\SignHasher' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/Signature/SignHasher.php',
        'Mdanter\\Ecc\\Crypto\\Signature\\Signature' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/Signature/Signature.php',
        'Mdanter\\Ecc\\Crypto\\Signature\\SignatureInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/Signature/SignatureInterface.php',
        'Mdanter\\Ecc\\Crypto\\Signature\\Signer' => __DIR__ . '/..' . '/mdanter/ecc/src/Crypto/Signature/Signer.php',
        'Mdanter\\Ecc\\Curves\\CurveFactory' => __DIR__ . '/..' . '/mdanter/ecc/src/Curves/CurveFactory.php',
        'Mdanter\\Ecc\\Curves\\NamedCurveFp' => __DIR__ . '/..' . '/mdanter/ecc/src/Curves/NamedCurveFp.php',
        'Mdanter\\Ecc\\Curves\\NistCurve' => __DIR__ . '/..' . '/mdanter/ecc/src/Curves/NistCurve.php',
        'Mdanter\\Ecc\\Curves\\SecgCurve' => __DIR__ . '/..' . '/mdanter/ecc/src/Curves/SecgCurve.php',
        'Mdanter\\Ecc\\EccFactory' => __DIR__ . '/..' . '/mdanter/ecc/src/EccFactory.php',
        'Mdanter\\Ecc\\Exception\\ExchangeException' => __DIR__ . '/..' . '/mdanter/ecc/src/Exception/ExchangeException.php',
        'Mdanter\\Ecc\\Exception\\NumberTheoryException' => __DIR__ . '/..' . '/mdanter/ecc/src/Exception/NumberTheoryException.php',
        'Mdanter\\Ecc\\Exception\\PointException' => __DIR__ . '/..' . '/mdanter/ecc/src/Exception/PointException.php',
        'Mdanter\\Ecc\\Exception\\PointNotOnCurveException' => __DIR__ . '/..' . '/mdanter/ecc/src/Exception/PointNotOnCurveException.php',
        'Mdanter\\Ecc\\Exception\\PointRecoveryException' => __DIR__ . '/..' . '/mdanter/ecc/src/Exception/PointRecoveryException.php',
        'Mdanter\\Ecc\\Exception\\PublicKeyException' => __DIR__ . '/..' . '/mdanter/ecc/src/Exception/PublicKeyException.php',
        'Mdanter\\Ecc\\Exception\\SignatureDecodeException' => __DIR__ . '/..' . '/mdanter/ecc/src/Exception/SignatureDecodeException.php',
        'Mdanter\\Ecc\\Exception\\SquareRootException' => __DIR__ . '/..' . '/mdanter/ecc/src/Exception/SquareRootException.php',
        'Mdanter\\Ecc\\Exception\\UnsupportedCurveException' => __DIR__ . '/..' . '/mdanter/ecc/src/Exception/UnsupportedCurveException.php',
        'Mdanter\\Ecc\\Math\\DebugDecorator' => __DIR__ . '/..' . '/mdanter/ecc/src/Math/DebugDecorator.php',
        'Mdanter\\Ecc\\Math\\GmpMath' => __DIR__ . '/..' . '/mdanter/ecc/src/Math/GmpMath.php',
        'Mdanter\\Ecc\\Math\\GmpMathInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Math/GmpMathInterface.php',
        'Mdanter\\Ecc\\Math\\MathAdapterFactory' => __DIR__ . '/..' . '/mdanter/ecc/src/Math/MathAdapterFactory.php',
        'Mdanter\\Ecc\\Math\\ModularArithmetic' => __DIR__ . '/..' . '/mdanter/ecc/src/Math/ModularArithmetic.php',
        'Mdanter\\Ecc\\Math\\NumberTheory' => __DIR__ . '/..' . '/mdanter/ecc/src/Math/NumberTheory.php',
        'Mdanter\\Ecc\\Primitives\\CurveFp' => __DIR__ . '/..' . '/mdanter/ecc/src/Primitives/CurveFp.php',
        'Mdanter\\Ecc\\Primitives\\CurveFpInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Primitives/CurveFpInterface.php',
        'Mdanter\\Ecc\\Primitives\\CurveParameters' => __DIR__ . '/..' . '/mdanter/ecc/src/Primitives/CurveParameters.php',
        'Mdanter\\Ecc\\Primitives\\GeneratorPoint' => __DIR__ . '/..' . '/mdanter/ecc/src/Primitives/GeneratorPoint.php',
        'Mdanter\\Ecc\\Primitives\\Point' => __DIR__ . '/..' . '/mdanter/ecc/src/Primitives/Point.php',
        'Mdanter\\Ecc\\Primitives\\PointInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Primitives/PointInterface.php',
        'Mdanter\\Ecc\\Random\\DebugDecorator' => __DIR__ . '/..' . '/mdanter/ecc/src/Random/DebugDecorator.php',
        'Mdanter\\Ecc\\Random\\HmacRandomNumberGenerator' => __DIR__ . '/..' . '/mdanter/ecc/src/Random/HmacRandomNumberGenerator.php',
        'Mdanter\\Ecc\\Random\\RandomGeneratorFactory' => __DIR__ . '/..' . '/mdanter/ecc/src/Random/RandomGeneratorFactory.php',
        'Mdanter\\Ecc\\Random\\RandomNumberGenerator' => __DIR__ . '/..' . '/mdanter/ecc/src/Random/RandomNumberGenerator.php',
        'Mdanter\\Ecc\\Random\\RandomNumberGeneratorInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Random/RandomNumberGeneratorInterface.php',
        'Mdanter\\Ecc\\Serializer\\Point\\CompressedPointSerializer' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/Point/CompressedPointSerializer.php',
        'Mdanter\\Ecc\\Serializer\\Point\\PointSerializerInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/Point/PointSerializerInterface.php',
        'Mdanter\\Ecc\\Serializer\\Point\\UncompressedPointSerializer' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/Point/UncompressedPointSerializer.php',
        'Mdanter\\Ecc\\Serializer\\PrivateKey\\DerPrivateKeySerializer' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/PrivateKey/DerPrivateKeySerializer.php',
        'Mdanter\\Ecc\\Serializer\\PrivateKey\\PemPrivateKeySerializer' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/PrivateKey/PemPrivateKeySerializer.php',
        'Mdanter\\Ecc\\Serializer\\PrivateKey\\PrivateKeySerializerInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/PrivateKey/PrivateKeySerializerInterface.php',
        'Mdanter\\Ecc\\Serializer\\PublicKey\\DerPublicKeySerializer' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/PublicKey/DerPublicKeySerializer.php',
        'Mdanter\\Ecc\\Serializer\\PublicKey\\Der\\Formatter' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/PublicKey/Der/Formatter.php',
        'Mdanter\\Ecc\\Serializer\\PublicKey\\Der\\Parser' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/PublicKey/Der/Parser.php',
        'Mdanter\\Ecc\\Serializer\\PublicKey\\PemPublicKeySerializer' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/PublicKey/PemPublicKeySerializer.php',
        'Mdanter\\Ecc\\Serializer\\PublicKey\\PublicKeySerializerInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/PublicKey/PublicKeySerializerInterface.php',
        'Mdanter\\Ecc\\Serializer\\Signature\\DerSignatureSerializer' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/Signature/DerSignatureSerializer.php',
        'Mdanter\\Ecc\\Serializer\\Signature\\DerSignatureSerializerInterface' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/Signature/DerSignatureSerializerInterface.php',
        'Mdanter\\Ecc\\Serializer\\Signature\\Der\\Formatter' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/Signature/Der/Formatter.php',
        'Mdanter\\Ecc\\Serializer\\Signature\\Der\\Parser' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/Signature/Der/Parser.php',
        'Mdanter\\Ecc\\Serializer\\Util\\CurveOidMapper' => __DIR__ . '/..' . '/mdanter/ecc/src/Serializer/Util/CurveOidMapper.php',
        'Mdanter\\Ecc\\Util\\BinaryString' => __DIR__ . '/..' . '/mdanter/ecc/src/Util/BinaryString.php',
        'Mdanter\\Ecc\\Util\\NumberSize' => __DIR__ . '/..' . '/mdanter/ecc/src/Util/NumberSize.php',
        'Symfony\\Polyfill\\Mbstring\\Mbstring' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/Mbstring.php',
        'Web3p\\RLP\\RLP' => __DIR__ . '/..' . '/web3p/rlp/src/RLP.php',
        'Web3p\\RLP\\Types\\Numeric' => __DIR__ . '/..' . '/web3p/rlp/src/Types/Numeric.php',
        'Web3p\\RLP\\Types\\Str' => __DIR__ . '/..' . '/web3p/rlp/src/Types/Str.php',
        'kornrunner\\Ethereum\\Address' => __DIR__ . '/..' . '/kornrunner/ethereum-address/src/Address.php',
        'kornrunner\\Ethereum\\Transaction' => __DIR__ . '/..' . '/kornrunner/ethereum-offline-raw-tx/src/Ethereum/Transaction.php',
        'kornrunner\\Keccak' => __DIR__ . '/..' . '/kornrunner/keccak/src/Keccak.php',
        'kornrunner\\Secp256k1' => __DIR__ . '/..' . '/kornrunner/secp256k1/src/Secp256k1.php',
        'kornrunner\\Serializer\\HexPrivateKeySerializer' => __DIR__ . '/..' . '/kornrunner/secp256k1/src/Serializer/HexPrivateKeySerializer.php',
        'kornrunner\\Serializer\\HexSignatureSerializer' => __DIR__ . '/..' . '/kornrunner/secp256k1/src/Serializer/HexSignatureSerializer.php',
        'kornrunner\\Signature\\Signature' => __DIR__ . '/..' . '/kornrunner/secp256k1/src/Signature/Signature.php',
        'kornrunner\\Signature\\SignatureInterface' => __DIR__ . '/..' . '/kornrunner/secp256k1/src/Signature/SignatureInterface.php',
        'kornrunner\\Signature\\Signer' => __DIR__ . '/..' . '/kornrunner/secp256k1/src/Signature/Signer.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit55be0a7fc0c96572f2f4290e4d3fbdf4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit55be0a7fc0c96572f2f4290e4d3fbdf4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit55be0a7fc0c96572f2f4290e4d3fbdf4::$classMap;

        }, null, ClassLoader::class);
    }
}
